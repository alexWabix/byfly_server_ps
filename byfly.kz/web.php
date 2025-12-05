<!DOCTYPE html>
<html lang="<?= $lang; ?>"> 

<head>
    <title><?= getTextTranslate(1); ?></title>
    <meta name="description" content="<?= getTextTranslate(2); ?>">
    <meta name="keywords" content="<?= getTextTranslate(3); ?>">
    <meta name="author" content="ByFly Team">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="https://byfly.kz/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://byfly.kz/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://byfly.kz/favicon/favicon-16x16.png">
    <link rel="manifest" href="https://byfly.kz/favicon/site.webmanifest">
    <link rel="mask-icon" href="https://byfly.kz/favicon/safari-pinned-tab.svg" color="#ff0000">
    <link rel="shortcut icon" href="https://byfly.kz/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#b91d47">
    <meta name="msapplication-config" content="https://byfly.kz/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="dist/style.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
</head>

<body class="js-preload-me ">
    <?php
    include($dirSuite . 'includes/preloader.php');
    ?>
    <div class="page js-page ">
        <?php
        include($dirSuite . 'includes/header.php');
        ?>

        <div class="hero hero--skin-1 ">
            <div class="container">
                <div class="row">
                    <div class="col-md-14">
                        <div class="push push--150"> </div>
                        <h2 class="section-title section-title--left section-title--light ">
                            <?= getTextTranslate(10); ?>
                            <span class='section-title__highlight'><?= getTextTranslate(11); ?></span>
                            <?= getTextTranslate(12); ?>
                        </h2>
                        <p class="section-subtitle section-subtitle--left section-subtitle--hero ">
                            <?= getTextTranslate(13); ?>
                        </p>

                        <div class="button button--small button--inline button--blue button--shadow">
                            <a href="#download" class="button__link js-scroll-to">
                                <?= getTextTranslate(9); ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <div class="device device--right">
                            <img src="assets/img/home-iphone.png" class="device__image" alt="ByFly Travel Application">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <section class="section section--grey " id='download'>
            <div class="container">
                <div class="row">
                    <div class="col-md-24">
                        <h2 class="section-title section-title--center ">
                            <?= getTextTranslate(14); ?>
                        </h2>
                        <p class="section-subtitle section-subtitle--center "><?= getTextTranslate(15); ?> </p>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-lg-8 col-md-8 col-lg-offset-4">
                        <a onclick="downloadAplication('android', <?= $_GET['agent']; ?>)"
                            class="download-button download-button--large" href="#">
                            <img style="height: 45px;" src="assets/img/google-play.svg" class="download-button__icon"
                                alt="Иконка Android">
                            <span class="download-button__platform"><?= getTextTranslate(16); ?> </span>
                            <span class="download-button__store">
                                <strong><?= getTextTranslate(17); ?></strong>
                            </span>
                        </a>
                    </div>
                    <div class="col-lg-8 col-md-8 col-lg-offset-1">
                        <a onclick="downloadAplication('ios', <?= $_GET['agent']; ?>)"
                            class="download-button download-button--large" href="#">
                            <img style="height: 45px;" src="assets/img/appstore.svg" class="download-button__icon"
                                alt="Иконка iOS">
                            <span class="download-button__platform"><?= getTextTranslate(18); ?></span>
                            <span class="download-button__store">
                                <strong><?= getTextTranslate(19); ?></strong>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section ">
            <div class="container">
                <div class="tv-search-form tv-moduleid-9967356"></div>
                <script type="text/javascript" src="//tourvisor.ru/module/init.js"></script>
            </div>
        </section>


        <section class="section ">
            <div class="container">
                <link rel="stylesheet" href="https://widget.gocruise.ru/css/app.css">
                <script>
                    cesSettings = {
                        email: "byfly.kz@mail.ru",
                        currencies: ["KZT"],
                    }
                </script>
                <script src="https://widget.gocruise.ru/js/app.js"></script>
            </div>
        </section>




        <section class="section  " id='features'>
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="push push--60"> </div>
                        <h2 class="section-title"><?= getTextTranslate(20); ?></h2>
                        <p class="section-subtitle"><?= getTextTranslate(21); ?></p>
                        <ul class="list list--check">
                            <li class="list__item"><?= getTextTranslate(22); ?></li>
                            <li class="list__item"><?= getTextTranslate(23); ?></li>
                            <li class="list__item"><?= getTextTranslate(24); ?></li>
                        </ul>
                        <ul class="list list--boxes">
                            <li class="list__item">
                                <img onclick="downloadAplication('ios', <?= $_GET['agent']; ?>)"
                                    src="assets/img/app-store-icon.svg" class="box__icon"
                                    alt="<?= getTextTranslate(25); ?>">
                            </li>
                            <li class="list__item">
                                <img onclick="downloadAplication('android', <?= $_GET['agent']; ?>)"
                                    src="assets/img/google-play-icon.png" class="box__icon"
                                    alt="<?= getTextTranslate(26); ?>">
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-12 col-md-11 col-md-offset-1 col-lg-offset-0">
                        <div class="screens-preview screens-preview--right  ">
                            <img src="assets/img/iphone-1.png" class="screens-preview__primary"
                                alt="<?= getTextTranslate(27); ?>">
                            <img src="assets/img/iphone-2.png" class="screens-preview__secondary"
                                alt="<?= getTextTranslate(27); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-13 col-md-13">
                        <div class="screens-preview  ">
                            <img src="assets/img/iphone-3.png" class="screens-preview__primary"
                                alt="<?= getTextTranslate(27); ?>">
                            <img src="assets/img/iphone-4.png" class="screens-preview__secondary"
                                alt="<?= getTextTranslate(27); ?>">
                        </div>
                    </div>
                    <div class="col-lg-11 col-md-10 col-lg-offset-0 col-md-offset-1">
                        <div class="push push--100  "> </div>
                        <h2 class="section-title ">
                            <?= getTextTranslate(28); ?>
                        </h2>
                        <p class="section-subtitle "><?= getTextTranslate(29); ?></p>
                        <ul class="accordion js-accordion ">
                            <li class="accordion__item">
                                <h3 class="accordion__title">
                                    <img src="assets/img/tenge_up.svg" class="accordion__icon"
                                        alt="Icon"><?= getTextTranslate(30); ?>
                                </h3>
                                <div class="accordion__content">
                                    <p><?= getTextTranslate(31); ?></p>
                                </div>
                            </li>
                            <li class="accordion__item">
                                <h3 class="accordion__title">
                                    <img src="assets/img/travel.svg" class="accordion__icon" alt="Icon">
                                    <?= getTextTranslate(94); ?>
                                </h3>
                                <div class="accordion__content">
                                    <p><?= getTextTranslate(32); ?></p>
                                </div>
                            </li>
                            <li class="accordion__item">
                                <h3 class="accordion__title">
                                    <img src="assets/img/bill.svg" class="accordion__icon" alt="Icon">
                                    <?= getTextTranslate(33); ?>
                                </h3>
                                <div class="accordion__content">
                                    <p><?= getTextTranslate(34); ?></p>
                                </div>
                            </li>
                        </ul>
                        <ul style="margin-top: 30px;" class="list list--boxes">
                            <li class="list__item">
                                <img onclick="downloadAplication('ios', <?= $_GET['agent']; ?>)"
                                    src="assets/img/app-store-icon.svg" class="box__icon"
                                    alt="<?= getTextTranslate(27); ?>">
                            </li>
                            <li class="list__item">
                                <img onclick="downloadAplication('android', <?= $_GET['agent']; ?>)"
                                    src="assets/img/google-play-icon.png" class="box__icon"
                                    alt="<?= getTextTranslate(27); ?>">
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="push push--60"> </div>
                            <h2 class="section-title"><?= getTextTranslate(35); ?></h2>
                            <p class="section-subtitle"><?= getTextTranslate(36); ?></p>
                            <ul class="list list--check">
                                <li class="list__item"><?= getTextTranslate(37); ?></li>
                                <li class="list__item"><?= getTextTranslate(38); ?></li>
                                <li class="list__item"><?= getTextTranslate(39); ?></li>
                            </ul>
                            <ul class="list list--boxes">
                                <li class="list__item">
                                    <img onclick="downloadAplication('ios', <?= $_GET['agent']; ?>)"
                                        src="assets/img/app-store-icon.svg" class="box__icon"
                                        alt="<?= getTextTranslate(27); ?>">
                                </li>
                                <li class="list__item">
                                    <img onclick="downloadAplication('android', <?= $_GET['agent']; ?>)"
                                        src="assets/img/google-play-icon.png" class="box__icon"
                                        alt="<?= getTextTranslate(27); ?>">
                                </li>
                            </ul>
                        </div>

                        <div class="col-lg-12 col-md-11 col-md-offset-1 col-lg-offset-0">
                            <div class="screens-preview screens-preview--right  ">
                                <img src="assets/img/iphone-9.png" class="screens-preview__primary"
                                    alt="<?= getTextTranslate(27); ?>">
                                <img src="assets/img/iphone-8.png" class="screens-preview__secondary"
                                    alt="<?= getTextTranslate(27); ?>">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section class="section section--grey ">
            <div class="container">
                <div class="tv-hot-tours tv-moduleid-9974329"></div>
                <script type="text/javascript" src="//tourvisor.ru/module/init.js"></script>
            </div>
        </section>

        <div class="container">
            <div class="row">
                <div class="col-md-24">
                    <a href="https://www.youtube.com/channel/UCURdgtCIpdVm1EoN931WLAg"
                        class="section__button section__button--youtube"><?= getTextTranslate(40); ?></a>
                </div>
            </div>
        </div>
        <section class="section section--grey " id='video'>
            <div class="container">
                <div class="row">
                    <div class="col-md-24">
                        <h2 class="section-title section-title--center "><?= getTextTranslate(41); ?>
                            <span class='section-title__highlight'><?= getTextTranslate(42); ?></span>
                        </h2>
                        <p class="section-subtitle section-subtitle--center "><?= getTextTranslate(43); ?></p>
                    </div>
                </div>
            </div>

            <div class="video  ">
                <a href="https://www.youtube.com/watch?v=N926ablzYX0" class="video__holder js-video">
                    <img src="assets/img/video-tutorial.png" class="video__primary" alt="<?= getTextTranslate(27); ?>">
                    <img src="assets/img/video-tutorial2.png" class="video__secondary video__secondary--left"
                        alt="<?= getTextTranslate(27); ?>">
                    <img src="assets/img/video-tutorial2.png" class="video__secondary video__secondary--right"
                        alt="<?= getTextTranslate(27); ?>">
                    <span class="video__button"></span>
                </a>
            </div>
        </section>
        <section class="section section--skin-2 " id='screenshots'>
            <div class="container">
                <div class="row">
                    <div class="col-md-24">
                        <h2 class="section-title section-title--center "><?= getTextTranslate(44); ?>
                            <span class='section-title__highlight'><?= getTextTranslate(45); ?></span>
                        </h2>
                        <p class="section-subtitle section-subtitle--center ">
                            <?= getTextTranslate(46); ?>
                        </p>
                        <div class="tabs js-tabs ">
                            <ul class="tabs__header">
                                <li class="tabs__title">
                                    <a class="tabs__link" href="#firstTab">
                                        <img src="assets/img/login.svg" class="tabs__icon"
                                            alt="<?= getTextTranslate(27); ?>"><?= getTextTranslate(47); ?>
                                    </a>
                                </li>
                                <li class="tabs__title">
                                    <a class="tabs__link" href="#secondTab">
                                        <img src="assets/img/creative-thinking.svg" class="tabs__icon"
                                            alt="<?= getTextTranslate(27); ?>">
                                        <?= getTextTranslate(48); ?>
                                    </a>
                                </li>
                                <li class="tabs__title">
                                    <a class="tabs__link" href="#thirdTab">
                                        <img src="assets/img/tenge-white.svg" class="tabs__icon"
                                            alt="<?= getTextTranslate(27); ?>">
                                        <?= getTextTranslate(49); ?>
                                    </a>
                                </li>
                            </ul>
                            <div id="firstTab" class="tabs__content">
                                <div class="tabs__izometric">
                                    <img src="assets/img/interface/iphone-6.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/interface/iphone-3.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/interface/iphone-2.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/interface/iphone-5.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/interface/iphone-4.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/interface/iphone-1.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                </div>
                                <div class="tabs__regular">
                                    <div class="row">
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/interface/iphone-1.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/interface/iphone-4.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/interface/iphone-5.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="secondTab" class="tabs__content">
                                <div class="tabs__izometric">
                                    <img src="assets/img/vozmojnosti/iphone-1.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/vozmojnosti/iphone-3.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/vozmojnosti/iphone-5.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/vozmojnosti/iphone-2.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/vozmojnosti/iphone-4.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/vozmojnosti/iphone-6.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                </div>
                                <div class="tabs__regular">
                                    <div class="row">
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/vozmojnosti/iphone-6.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/vozmojnosti/iphone-4.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/vozmojnosti/iphone-2.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="thirdTab" class="tabs__content">
                                <div class="tabs__izometric">
                                    <img src="assets/img/pays/iphone-6.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/pays/iphone-5.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/pays/iphone-3.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/pays/iphone-4.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/pays/iphone-2.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                    <img src="assets/img/pays/iphone-1.png" class="tabs__screenshot"
                                        alt="Screen preview">
                                </div>
                                <div class="tabs__regular">
                                    <div class="row">
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/pays/iphone-1.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/pays/iphone-2.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                        <div class="col-md-8 col-sm-12 col-xs-24">
                                            <img src="assets/img/pays/iphone-4.png" class="tabs__screenshot"
                                                alt="Screen preview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="push push--150  pushStarnet"> </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section--large " style="padding-bottom: 0px; margin-bottom: 0px;">
            <div class="container">
                <div class="tv-min-price tv-moduleid-9974330"></div>
                <script type="text/javascript" src="//tourvisor.ru/module/init.js"></script>
            </div>
        </section>

        <section class="section section--large " id='gallery'>
            <div class="carousel  ">
                <div class="carousel__outer" style="padding-top: 0px;">
                    <div class="carousel__inner js-carousel owl-carousel">
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-1.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-2.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-3.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-4.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-5.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-6.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-7.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-8.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                        <div class="carousel__slide">
                            <img src="assets/img/iphone-9.png" class="carousel__image" alt="ByFly Mobile App">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="container">
            <div class="row">
                <div class="col-md-24">
                    <a href="https://twitter.com/VSargu" class="section__button section__button--twitter">
                        <img style="height: 15px; margin-right: 10px; margin-bottom: -2px;"
                            src="assets/img/notification.svg">
                        Подпишись на нас:</a>
                </div>
            </div>
        </div>
        <section class="section section--blue "
            style="background-image: url('assets/img/social-background.png'); background-size: cover; background-position: center center;"
            id='twitter'>
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-md-offset-9 col-xs-offset-3 col-sm-16 col-sm-offset-4">
                        <div class="row">
                            <div class="col-xs-5">
                                <a href="https://www.instagram.com/byfly.kz/" target="_blank">
                                    <img src="assets/img/instagram.svg" height="30px">
                                </a>
                            </div>
                            <div class="col-xs-5">
                                <a href="https://www.tiktok.com/@byfly.kz" target="_blank">
                                    <img src="assets/img/tiktok.png" height="30px">
                                </a>
                            </div>
                            <div class="col-xs-5">
                                <a href="https://www.youtube.com/@byflykz/addons/unicode-properties" target="_blank">
                                    <img src="assets/img/youtube.svg" height="30px">
                                </a>
                            </div>
                            <div class="col-xs-5">
                                <a href="https://whatsapp.com/channel/0029VaKj49fKLaHnXsJcLL1T" target="_blank">
                                    <img src="assets/img/whatsapp.svg" height="30px">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer style="margin-top: 100px; margin-bottom: 100px; padding-bottom: 100px;" class="footer ">
            <a href="#" class="footer__scroll-top js-scroll-top">
                <i class="fontello-level-up footer__scroll-icon"></i>
            </a>
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="logo ">
                            <a href="index.html" class="logo__link">
                                <img src="assets/img/common/logo/logo-dark.png" alt="<?= getTextTranslate(27); ?>"
                                    class="logo__image"> </a>
                        </div>
                        <p class="footer__message">
                            <?= getTextTranslate(50); ?><br><br><br>
                            <?= getTextTranslate(51); ?><br>
                            <?= getTextTranslate(52); ?><br>
                            <?= getTextTranslate(53); ?><br>
                            <?= getTextTranslate(54); ?><br><br>

                            <strong><?= getTextTranslate(55); ?></strong> <a href="https://wa.me/77789283828">+7 778
                                928 3828</a>
                        </p>
                    </div>

                    <div class="col-md-5 col-sm-12 col-xs-12 col-md-offset-2">
                        <nav class="link-list  ">
                            <ul>
                                <li class="link-list__item">
                                    <a href="index.html"
                                        class="link-list__link js-scroll-to"><?= getTextTranslate(4); ?></a>
                                </li>
                                <li class="link-list__item">
                                    <a href="#features"
                                        class="link-list__link js-scroll-to"><?= getTextTranslate(5); ?></a>
                                </li>
                                <li class="link-list__item">
                                    <a href="#gallery"
                                        class="link-list__link js-scroll-to"><?= getTextTranslate(7); ?></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <div class="col-md-5 col-sm-12 col-xs-12">
                        <nav class="link-list  ">
                            <ul>
                                <li class="link-list__item">
                                    <a href="#contacts"
                                        class="link-list__link js-scroll-to"><?= getTextTranslate(8); ?></a>
                                </li>
                                <li class="link-list__item">
                                    <a href="#download"
                                        class="link-list__link js-scroll-to"><?= getTextTranslate(9); ?></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <?php
    if ($_GET['agent'] != 0) {
        $text = '';
        if ($searchAgent['refer_registration_bonus'] > 0) {
            $text = '<p>' . getTextTranslate(57) . ' ' . number_format($searchAgent['refer_registration_bonus'], 0, '', ' ') . ' ' . getTextTranslate(58) . ' </p>';
        } else {
            $text = '<p>' . getTextTranslate(56) . '</p>';
        }
        echo '<div id="popup" onclick="closeModal()" class="popupWindow">
					<div class="popupBody" style="cursor: default;" onclick="event.stopPropagation();">
						<h3>' . getTextTranslate(59) . '</h3>
						' . $text . '

						<label class="contact-form__label" for="phone-input">' . getTextTranslate(60) . '</label>
						<div class="input input--contact  ">
							<input onkeyup="checkPhoneNumber()" type="text" name="phone" class="input__field"
								placeholder="' . getTextTranslate(60) . '" id="phone-input" data-validation="required">
						</div>
						<div id="errorMessagesSendCode" style="display: none;">' . getTextTranslate(61) . '</div>
						<div id="smsCodes" style="display: none; margin-bottom: 20px;">
							<label class="contact-form__label" for="phone-input">' . getTextTranslate(62) . '</label>
							<div class="input input--contact  ">
								<input onkeyup="checkedSmsCodes()" type="text" name="sms" class="input__field" placeholder=""
									id="sms-input" data-validation="required">
							</div>
							
						</div>
						<div id="whatsSendedCode" style="display: none;"></div>


						<div class="button button--small button--shadow  ">
							<button type="button" onclick="reg()" id="buttonSend" disabled="true" type="submit"
								class="button__input">
								<span id="btnText">' . getTextTranslate(63) . '</span> <span style="display: none;" class="loaderBtn"></span>
							</button>
						</div>
					</div>';
    }
    ?>


    <script src="https://maps.googleapis.com/maps/api/js"></script>
    <script src="dist/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/imask"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

    <script type="text/javascript">
        var linkForDownloadIos = 'https://apple.com/';
        var linkForDownloadAndroid = 'https://google.com/';
        var smsCodes = 0;
        var selectedType = '';

        function checkedSmsCodes() {
            if (smsCodes != 0) {
                if ($("#sms-input").val().replace('-', '') == smsCodes) {
                    registration();
                }
            }

        }

        function openDownloadPage() {
            if (selectedType == 'ios') {
                window.location.href = linkForDownloadIos;
            } else {
                window.location.href = linkForDownloadAndroid;
            }
        }

        async function registration() {
            var onlyNumber = $("#phone-input").val().replace(/\D/g, '');
            const data = new URLSearchParams();
            data.append('method', 'refer/registration_phone_last');
            data.append('phoneNumber', onlyNumber);
            data.append('agentId', <?= $_GET['agent']; ?>);
            data.append('lang', '<?= $lang; ?>');
            $("#buttonSend").attr('disabled', true);
            $("#btnText").hide();
            $(".loaderBtn").show();
            await axios.post('https://api.v.2.byfly.kz/index.php', data)
                .then(response => {
                    window.localStorage.setItem('registered', 'true');
                    openDownloadPage();
                })
                .catch(error => {
                    alert('<?= getTextTranslate(64) ?>');
                });
            $("#btnText").show();
            $(".loaderBtn").hide();
            $("#buttonSend").removeAttr('disabled');
        }

        function downloadAplication(type, agent) {
            var isRegistration = window.localStorage.getItem('registered');
            if (isRegistration != null) {
                openDownloadPage();
            } else {
                selectedType = type;
                if (agent != 0) {
                    $("#popup").addClass("show");
                } else {
                    openDownloadPage();
                }
            }

        }

        async function reg() {
            var isRegistration = window.localStorage.getItem('registered');
            if (isRegistration != null) {
                openDownloadPage();
            } else {
                if (smsCodes == 0) {
                    var onlyNumber = $("#phone-input").val().replace(/\D/g, '');
                    const data = new URLSearchParams();
                    data.append('method', 'refer/registration_phone');
                    data.append('phoneNumber', onlyNumber);
                    data.append('agentId', <?= $_GET['agent']; ?>);
                    data.append('lang', '<?= $lang; ?>');
                    $("#buttonSend").attr('disabled', true);
                    $("#btnText").hide();
                    $(".loaderBtn").show();
                    await axios.post('https://api.v.2.byfly.kz/index.php', data)
                        .then(response => {
                            if (response.data['type']) {
                                smsCodes = response.data['data']['code'];
                                $("#whatsSendedCode").show();
                                if (response.data['data']['tipical'] == 'whatsapp') {
                                    $("#whatsSendedCode").text('<?= getTextTranslate(65) ?>');
                                } else {
                                    $("#whatsSendedCode").text('<?= getTextTranslate(66) ?>');
                                }

                                $("#smsCodes").show();
                                $("#errorMessagesSendCode").hide();
                                $("#buttonSend").removeAttr('disabled');
                            } else {
                                $("#errorMessagesSendCode").text(response.data['msg']);
                                $("#errorMessagesSendCode").show();
                            }
                        })
                        .catch(error => {
                            alert('<?= getTextTranslate(67) ?>');
                        });
                    $("#btnText").show();
                    $(".loaderBtn").hide();
                    $("#buttonSend").removeAttr('disabled');
                } else {
                    if (smsCodes != 0) {
                        if ($("#sms-input").val().replace('-', '') == smsCodes) {
                            registration();
                        }
                    }
                }
            }
        }

        function checkPhoneNumber() {
            $("#smsCodes").hide();
            smsCodes = 0;
            var nlyNumber = $("#phone-input").val().replace(/\D/g, '');
            if (nlyNumber.length == 11) {
                $("#buttonSend").removeAttr('disabled');
            } else {
                $("#errorMessagesSendCode").hide();
                $("#buttonSend").attr('disabled', true);
            }
        }

        function closeModal() {
            $("#popup").removeClass("show");
        }

        $(document).ready(function () {
            <?php
            if ($_GET['agent'] != 0) {
                echo "const element = document.getElementById('phone-input');
							const maskOptions = {
								mask: '+{7} (000) 000-00-00'
							};
							const mask = IMask(element, maskOptions);


							const element2 = document.getElementById('sms-input');
							const maskOptions2 = {
								mask: '000-000'
							};
							const mask2 = IMask(element2, maskOptions2);";
            }
            ?>
        });
    </script>
</body>

</html>