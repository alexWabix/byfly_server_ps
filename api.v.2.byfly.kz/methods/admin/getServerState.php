<?php

/**
 * Получение загрузки CPU в процентах
 * Использует /proc/stat для более точного измерения
 */
function getCpuLoad()
{
    try {
        // Читаем данные из /proc/stat дважды с интервалом
        $stat1 = file_get_contents('/proc/stat');
        usleep(100000); // 0.1 секунды
        $stat2 = file_get_contents('/proc/stat');

        if (!$stat1 || !$stat2) {
            // Fallback на sys_getloadavg
            $load = sys_getloadavg();
            $cores = (int) shell_exec('nproc 2>/dev/null') ?: 1;
            return min(100, round(($load[0] / $cores) * 100, 1));
        }

        // Парсим первое измерение
        $lines1 = explode("\n", $stat1);
        $cpu1 = explode(' ', $lines1[0]);
        $idle1 = (int) $cpu1[4] + (int) $cpu1[5]; // idle + iowait
        $total1 = array_sum(array_slice($cpu1, 1, 7));

        // Парсим второе измерение
        $lines2 = explode("\n", $stat2);
        $cpu2 = explode(' ', $lines2[0]);
        $idle2 = (int) $cpu2[4] + (int) $cpu2[5]; // idle + iowait
        $total2 = array_sum(array_slice($cpu2, 1, 7));

        // Вычисляем разности
        $idle_diff = $idle2 - $idle1;
        $total_diff = $total2 - $total1;

        if ($total_diff == 0) {
            return 0.0;
        }

        // Вычисляем процент использования
        $usage = 100 - (($idle_diff / $total_diff) * 100);

        return round(max(0, min(100, $usage)), 1);

    } catch (Exception $e) {
        // В случае ошибки возвращаем 0
        return 0.0;
    }
}

/**
 * Получение использования памяти в процентах
 * Использует /proc/meminfo для более точных данных
 */
function getMemoryUsage()
{
    try {
        $meminfo = file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return getMemoryUsageFallback();
        }

        $lines = explode("\n", $meminfo);
        $memData = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(\d+)\s*kB/', $line, $matches)) {
                $memData[$matches[1]] = (int) $matches[2];
            }
        }

        $total = $memData['MemTotal'] ?? 0;
        $free = $memData['MemFree'] ?? 0;
        $buffers = $memData['Buffers'] ?? 0;
        $cached = $memData['Cached'] ?? 0;
        $sReclaimable = $memData['SReclaimable'] ?? 0;

        if ($total <= 0) {
            return 0.0;
        }

        // Доступная память = свободная + буферы + кеш + освобождаемая
        $available = $free + $buffers + $cached + $sReclaimable;
        $used = $total - $available;

        $percent = ($used / $total) * 100;

        return round(max(0, min(100, $percent)), 1);

    } catch (Exception $e) {
        return getMemoryUsageFallback();
    }
}

/**
 * Fallback метод для получения памяти через команду free
 */
function getMemoryUsageFallback()
{
    try {
        $free = shell_exec('free -b 2>/dev/null');
        if (!$free) {
            return 0.0;
        }

        $lines = explode("\n", trim($free));
        if (count($lines) < 2) {
            return 0.0;
        }

        // Парсим строку с памятью (в байтах для большей точности)
        $mem = preg_split('/\s+/', $lines[1]);
        if (count($mem) < 3) {
            return 0.0;
        }

        $total = (int) $mem[1];
        $used = (int) $mem[2];
        $available = isset($mem[6]) ? (int) $mem[6] : ((int) $mem[3] + (int) ($mem[5] ?? 0) + (int) ($mem[6] ?? 0));

        if ($total <= 0) {
            return 0.0;
        }

        // Если доступна колонка available, используем её
        if (isset($mem[6])) {
            $realUsed = $total - $available;
        } else {
            // Иначе вычисляем сами
            $free = (int) $mem[3];
            $buffers = isset($mem[5]) ? (int) $mem[5] : 0;
            $cached = isset($mem[6]) ? (int) $mem[6] : 0;
            $realUsed = $used - $buffers - $cached;
        }

        $percent = ($realUsed / $total) * 100;

        return round(max(0, min(100, $percent)), 1);

    } catch (Exception $e) {
        return 0.0;
    }
}

/**
 * Получение использования диска в процентах
 * Проверяет несколько разделов и возвращает наиболее загруженный
 */
function getDiskUsage()
{
    try {
        // Получаем информацию о всех смонтированных файловых системах
        $df = shell_exec('df -h --output=target,pcent 2>/dev/null | grep -E "^(/|/var|/home|/tmp)" | head -5');

        if (!$df) {
            // Fallback на простую команду df
            $df = shell_exec('df -h / 2>/dev/null');
            if (!$df) {
                return 0.0;
            }

            $lines = explode("\n", trim($df));
            if (count($lines) < 2) {
                return 0.0;
            }

            $parts = preg_split('/\s+/', $lines[1]);
            if (count($parts) < 5) {
                return 0.0;
            }

            $used = rtrim($parts[4], '%');
            return (float) $used;
        }

        $lines = explode("\n", trim($df));
        $maxUsage = 0;

        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;

            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2) {
                $usage = (float) rtrim($parts[1], '%');
                $maxUsage = max($maxUsage, $usage);
            }
        }

        return $maxUsage;

    } catch (Exception $e) {
        return 0.0;
    }
}

/**
 * Получение uptime в читаемом формате
 */
function getUptime()
{
    try {
        // Сначала пробуем /proc/uptime для точности
        $uptime_file = file_get_contents('/proc/uptime');
        if ($uptime_file) {
            $uptime_seconds = (float) explode(' ', trim($uptime_file))[0];
            return formatUptime($uptime_seconds);
        }

        // Fallback на команду uptime
        $uptime = shell_exec('uptime -p 2>/dev/null');
        if ($uptime) {
            return trim(str_replace(
                ['up ', ' weeks', ' week', ' days', ' day', ' hours', ' hour', ' minutes', ' minute'],
                ['', 'н', 'н', 'д', 'д', 'ч', 'ч', 'м', 'м'],
                $uptime
            ));
        }

        // Последний fallback
        $uptime = shell_exec('uptime 2>/dev/null');
        if ($uptime && preg_match('/up\s+(.+?),\s+\d+\s+users?/', $uptime, $matches)) {
            return trim($matches[1]);
        }

        return 'Неизвестно';

    } catch (Exception $e) {
        return 'Ошибка';
    }
}

/**
 * Форматирование uptime из секунд в читаемый вид
 */
function formatUptime($seconds)
{
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    $parts = [];

    if ($days > 0) {
        $parts[] = $days . 'д';
    }
    if ($hours > 0) {
        $parts[] = $hours . 'ч';
    }
    if ($minutes > 0 || empty($parts)) {
        $parts[] = $minutes . 'м';
    }

    return implode(' ', $parts);
}

/**
 * Получение информации об ОС
 */
function getOSInfo()
{
    try {
        // Пробуем получить из /etc/os-release
        $os_release = file_get_contents('/etc/os-release');
        if ($os_release && preg_match('/PRETTY_NAME="([^"]+)"/', $os_release, $matches)) {
            return $matches[1];
        }

        // Fallback методы
        $methods = [
            'lsb_release -d 2>/dev/null | cut -f2',
            'cat /etc/redhat-release 2>/dev/null',
            'cat /etc/debian_version 2>/dev/null | head -1',
            'uname -s 2>/dev/null'
        ];

        foreach ($methods as $method) {
            $result = shell_exec($method);
            if ($result && trim($result) !== '') {
                return trim($result);
            }
        }

        return 'Linux';

    } catch (Exception $e) {
        return 'Неизвестно';
    }
}

/**
 * Получение дополнительной информации о системе
 */
function getSystemInfo()
{
    try {
        $info = [];

        // Количество ядер процессора
        $cores = (int) shell_exec('nproc 2>/dev/null') ?: 1;
        $info['cpu_cores'] = $cores;

        // Модель процессора
        $cpu_info = shell_exec('cat /proc/cpuinfo | grep "model name" | head -1 | cut -d: -f2 2>/dev/null');
        $info['cpu_model'] = $cpu_info ? trim($cpu_info) : 'Неизвестно';

        // Общий объем памяти
        $mem_total = shell_exec('cat /proc/meminfo | grep MemTotal | awk \'{print $2}\' 2>/dev/null');
        $info['memory_total_gb'] = $mem_total ? round((int) $mem_total / 1024 / 1024, 1) : 0;

        // Загрузка за последние 1, 5, 15 минут
        $load = sys_getloadavg();
        $info['load_average'] = [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2)
        ];

        return $info;

    } catch (Exception $e) {
        return [];
    }
}

// Получаем все данные о сервере
try {
    $systemInfo = getSystemInfo();

    $serverData = [
        'cpu' => getCpuLoad(),
        'ram' => getMemoryUsage(),
        'disk' => getDiskUsage(),
        'uptime' => getUptime(),
        'os' => getOSInfo(),
        'hostname' => gethostname() ?: 'localhost',
        'time' => date('Y-m-d H:i:s'),
        'timestamp' => time(),

        // Дополнительная информация
        'cpu_cores' => $systemInfo['cpu_cores'] ?? 1,
        'cpu_model' => $systemInfo['cpu_model'] ?? 'Неизвестно',
        'memory_total_gb' => $systemInfo['memory_total_gb'] ?? 0,
        'load_average' => $systemInfo['load_average'] ?? ['1min' => 0, '5min' => 0, '15min' => 0],

        // Статус сервера
        'status' => 'online',
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно'
    ];

    // Формируем успешный ответ
    $response = [
        'type' => true,
        'code' => 0,
        'data' => $serverData,
        'message' => 'Данные сервера получены успешно'
    ];

} catch (Exception $e) {
    // В случае критической ошибки
    $response = [
        'type' => false,
        'code' => 500,
        'message' => 'Ошибка получения данных сервера: ' . $e->getMessage(),
        'data' => [
            'cpu' => 0,
            'ram' => 0,
            'disk' => 0,
            'uptime' => 'Ошибка',
            'os' => 'Неизвестно',
            'hostname' => 'localhost',
            'time' => date('Y-m-d H:i:s'),
            'status' => 'error'
        ]
    ];
}

// Устанавливаем правильные заголовки
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Возвращаем JSON-ответ
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>