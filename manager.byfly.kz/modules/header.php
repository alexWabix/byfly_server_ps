<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item ">
                    <a class="nav-link activeMenu" href="index.php" style="color: white;" role="button">
                        <i class="ion-android-list"></i> <?= $userInfo['type'] == 1 ? 'Все заявки' : 'Мои заявки' ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle colorWhite" href="index.php?page=raschetes"
                        id="navbarDropdownMenuLink2" style="color: white;" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="ion-ios-cart"></i> Расчеты и документы
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink2">
                        <li><a class="dropdown-item" href="index.php?page=zarplata">Зарплата</a></li>
                        <li <?= empty($userInfo['linkDogovor']) ? 'style="display: none;"' : '' ?>><a
                                class="dropdown-item" href="<?= $userInfo['linkDogovor'] ?>">Трудовой договор</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link colorWhite" href="index.php?page=crm" style="color: white;" role="button">
                        <i class="ion-android-checkbox-outline"></i> Мои задачи
                    </a>
                </li>
                <li <?= $userInfo['type'] == 1 ? '' : 'style="display: none;"' ?> class="nav-item">
                    <a class="nav-link colorWhite" href="index.php?page=personal" style="color: white;" role="button">
                        <i class="ion-ios-people"></i> Сотрудники
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link colorWhite" href="index.php?page=mekka_hotels" style="color: white;"
                        role="button">
                        <i class="ion-ios-albums-outline"></i> Отели Мекка
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>