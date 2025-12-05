<?php
header('Content-Type: application/json; charset=utf-8');

// Проверка наличия SQL-запроса
if (!isset($_POST['sql']) || empty(trim($_POST['sql']))) {
    echo json_encode(['type' => false, 'msg' => 'Некорректный параметр']);
    exit;
}

// Проверка ключа безопасности
if ($_POST['key'] != '037fb3d21f1d5eebb79664aefc422ae9583a7ad940ac6853a6b81cf14ce4cd6f') {
    echo json_encode(['type' => false, 'msg' => 'Некорректный ключ безопасности']);
    exit;
}


// Подготовка SQL-запроса
$sql = trim($_POST['sql']);
$isSelectQuery = stripos($sql, 'SELECT') === 0;
$isMultipleQueries = strpos($sql, ';') !== false && !$isSelectQuery;

// Проверяем, нужно ли форматировать типы колонок (по умолчанию - да)
$checkColumn = $_POST['check_column'] == '1' or $_POST['check_column'] == 1;


// Функция для определения типа поля MySQL
function getFieldType($field)
{
    $type = $field->type;
    $flags = $field->flags;

    // Если поле называется phone или user_phone, всегда возвращаем string
    if (strtolower($field->name) === 'phone' || strtolower($field->name) === 'user_phone') {
        return 'string';
    }

    // Определяем числовые типы
    if (
        $type === MYSQLI_TYPE_TINY || $type === MYSQLI_TYPE_SHORT ||
        $type === MYSQLI_TYPE_LONG || $type === MYSQLI_TYPE_LONGLONG ||
        $type === MYSQLI_TYPE_INT24
    ) {
        return 'integer';
    }

    // Определяем типы с плавающей точкой
    if (
        $type === MYSQLI_TYPE_FLOAT || $type === MYSQLI_TYPE_DOUBLE ||
        $type === MYSQLI_TYPE_DECIMAL || $type === MYSQLI_TYPE_NEWDECIMAL
    ) {
        return 'float';
    }

    // Определяем булевы значения
    if ($type === MYSQLI_TYPE_BIT || ($type === MYSQLI_TYPE_TINY && $field->length === 1)) {
        return 'boolean';
    }

    // По умолчанию возвращаем строку
    return 'string';
}

// Функция для преобразования значения в правильный тип
function convertValue($value, $type)
{
    if ($value === null) {
        return null;
    }

    switch ($type) {
        case 'integer':
            return (int) $value;
        case 'float':
            return (float) $value;
        case 'boolean':
            return (bool) $value;
        default:
            return (string) $value; // строки оставляем как есть, но явно приводим к строке
    }
}

// Функция для обработки результатов с правильной типизацией
function processResult($db, $result, $isSelectQuery, $checkColumn)
{
    if ($result) {
        if ($isSelectQuery) {
            $data = [];

            if ($checkColumn) {
                // Получаем метаданные о полях и преобразуем типы
                $fields = $result->fetch_fields();
                $fieldTypes = [];

                foreach ($fields as $field) {
                    $fieldTypes[$field->name] = getFieldType($field);
                }

                while ($row = $result->fetch_assoc()) {
                    $typedRow = [];
                    foreach ($row as $key => $value) {
                        $typedRow[$key] = convertValue($value, $fieldTypes[$key]);
                    }
                    $data[] = $typedRow;
                }
            } else {
                // Без проверки типов - возвращаем данные как есть
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }


            if ($checkColumn) {
                return json_encode([
                    'type' => true,
                    'data' => $data,
                    'msg' => $checkColumn,
                ], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            } else {
                return json_encode([
                    'type' => true,
                    'data' => $data,
                    'msg' => $checkColumn,
                ], JSON_UNESCAPED_UNICODE);
            }


        } else {
            if ($checkColumn) {
                return json_encode([
                    'type' => true,
                    'data' => [
                        "affected_rows" => $db->affected_rows,
                        'msg' => 'Изменения успешно выполнены'
                    ]
                ], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            } else {
                return json_encode([
                    'type' => true,
                    'data' => [
                        "affected_rows" => $db->affected_rows,
                        'msg' => 'Изменения успешно выполнены'
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }

        }
    } else {
        return json_encode([
            'type' => false,
            'msg' => 'Ошибка запроса: ' . $db->error
        ], JSON_UNESCAPED_UNICODE);
    }
}

try {
    if ($isMultipleQueries) {
        if ($db->multi_query($sql)) {
            do {
                if ($result = $db->store_result()) {
                    $result->free();
                }
            } while ($db->more_results() && $db->next_result());

            echo json_encode([
                'type' => true,
                'msg' => 'Множественные запросы выполнены успешно'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($db->error);
        }
    } else {
        $result = $db->query($sql);
        echo processResult($db, $result, $isSelectQuery, $checkColumn);
    }
} catch (Exception $e) {
    echo json_encode([
        'type' => false,
        'msg' => 'Ошибка: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$db->close();
?>