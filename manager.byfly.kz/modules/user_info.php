<?php
$fio = $userInfo['fio'];
$initials = '';
$initials2 = '';

if (!empty($fio)) {
    $nameParts = explode(' ', $fio);
    if (count($nameParts) >= 2) {
        $initials .= strtoupper(mb_substr($nameParts[0], 0, 1));
        $initials .= strtoupper(mb_substr($nameParts[1], 0, 1));

        $initials2 .= $nameParts[0];
        $initials2 .= ' ' . strtoupper(mb_substr($nameParts[1], 0, 1)) . '.';
    }
}

$backgroundColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
$avatar = $userInfo['avatar'];

$avatarStyle = 'display: flex; justify-content: center; align-items: center; box-sizing: border-box; border: 1px dashed #cccccc; padding: 4px; border-radius: 50%; float: left; width: 38px; height: 38px;';

if (empty($avatar)) {
    $avatarContent = '<div style="' . $avatarStyle . ' background-color: ' . $backgroundColor . '; font-weight: bold; font-size: 12px; color: white;">
                        ' . $initials . '
                      </div>';
} else {
    $avatarContent = '<div style="' . $avatarStyle . '"><img src="' . $avatar . '" alt="' . $fio . '" class="rounded-circle" width="30" height="30" /></div>';
}
?>

<li class="nav-item dropdown" style="position: relative;">
    <a class="nav-link dropdown-toggle" style="color: white; display: flex; align-items: center;" href="#" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        <?= $avatarContent ?>
        <span style="margin-left: 10px;"><?= $initials2 ?></span>
    </a>
    <ul class="dropdown-menu" style="position: absolute; left: 0;">
        <li><a class="dropdown-item" style="color: black;" href="index.php?page=settings"><i class="ion-gear-a"></i>
                Настройки</a></li>
        <li><a class="dropdown-item" style="color: black;" href="index.php?page=logoute"><i class="ion-log-out"></i>
                Выйти</a></li>
    </ul>
</li>