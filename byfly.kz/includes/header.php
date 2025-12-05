<div id="sidr">
    <div class="mobile-menu__close js-mobile-menu__close"></div>
    <ul class="mobile-menu js-mobile-menu ">
        <li class="mobile-menu__item ">
            <a href="index.html" class="mobile-menu__link"><?= getTextTranslate(4); ?></a>
        </li>
        <li class="mobile-menu__item ">
            <a href="#features" class="mobile-menu__link js-scroll-to">О
                <?= getTextTranslate(5); ?>
            </a>
        </li>
        <li class="mobile-menu__item ">
            <a href="#team" class="mobile-menu__link js-scroll-to"><?= getTextTranslate(6); ?></a>
        </li>
        <li class="mobile-menu__item ">
            <a href="#gallery" class="mobile-menu__link js-scroll-to"><?= getTextTranslate(7); ?></a>
        </li>
        <li class="mobile-menu__item ">
            <a href="#contacts" class="mobile-menu__link js-scroll-to"><?= getTextTranslate(8); ?></a>
        </li>
        <li class="mobile-menu__item mobile-menu__item--button">
            <a href="#download" class="mobile-menu__link js-scroll-to">
                <?= getTextTranslate(9); ?>
            </a>
        </li>
    </ul>
</div>
<header class="header js-header ">
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="logo ">
                    <a href="index.html" class="logo__link">
                        <img src="assets/img/common/logo/logo.png" alt="ByFly Travel App logo"
                            class="logo__image js-logo__image" data-switch="true">
                    </a>
                    <ul class="listLangs">
                        <?php
                        $addRu = '&lang=ru';
                        $addEn = '&lang=en';
                        $addKk = '&lang=kk';
                        if (empty($_GET['lang']) == false) {
                            unset($_GET['lang']);
                        }
                        ?>
                        <li <?php if ($lang == 'ru') {
                            echo 'class="active"';
                        } ?>><a
                                href="index.php?<?= http_build_query($_GET) . $addRu ?>">RU</a>
                        </li>
                        <li <?php if ($lang == 'kk') {
                            echo 'class="active"';
                        } ?>><a
                                href="index.php?<?= http_build_query($_GET) . $addKk ?>">KZ</a></li>
                        <li <?php if ($lang == 'en') {
                            echo 'class="active"';
                        } ?>><a
                                href="index.php?<?= http_build_query($_GET) . $addEn ?>">EN</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-20 col-sm-20 col-xs-12">
                <nav>
                    <ul class="menu menu--right js-menu sf-menu ">
                        <li class="menu__item ">
                            <a href="index.html" class="menu__link"><?= getTextTranslate(4); ?></a>
                        </li>
                        <li class="menu__item ">
                            <a href="#features" class="menu__link js-scroll-to"><?= getTextTranslate(5); ?></a>
                        </li>
                        <li class="menu__item ">
                            <a href="#team" class="menu__link js-scroll-to"><?= getTextTranslate(6); ?></a>
                        </li>
                        <li class="menu__item ">
                            <a href="#gallery" class="menu__link js-scroll-to"><?= getTextTranslate(7); ?></a>
                        </li>
                        <li class="menu__item ">
                            <a href="#contacts" class="menu__link js-scroll-to"><?= getTextTranslate(8); ?></a>
                        </li>
                        <li class="menu__item menu__item--button">
                            <a href="#download" class="menu__link js-scroll-to"><?= getTextTranslate(9); ?></a>
                        </li>
                    </ul>
                </nav>
                <a class="menu-trigger js-menu-trigger " href='sidr'> </a>
            </div>
        </div>
    </div>
</header>