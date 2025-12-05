<?php
$dir = '/var/www/www-root/data/www/api.v.2.byfly.kz/';
$dirSuite = '/var/www/www-root/data/www/byfly.kz/';
include($dirSuite . 'includes/start.php');
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>">

<head>
	<title><?= getTextTranslate(92); ?></title>
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

<body class="js-preload-me error-page">
	<?php
	include($dirSuite . 'includes/preloader.php');
	?>
	<div class="page js-page ">
		<?php
		include($dirSuite . 'includes/header.php');
		?>
		<div class="error  ">
			<h1 class="error__title">
				<span class="error__highlight"> <?= getTextTranslate(88); ?></span>
				<br> <?= getTextTranslate(89); ?>
			</h1>
			<div class="error__wrapper">
				<img src="assets/img/404.png" alt="404 Error Image" style="width: 200px;" class="error__image">
				<span class="error__circle error__circle--primary">
					<span class="error__circle error__circle--primary-inner"></span>
					<span class="error__circle error__circle--secondary">
						<span class="error__circle error__circle--secondary-inner"></span>
					</span>
				</span>
				<a href="index.html" class="error__link">
					<span class="error__arrow">←</span> <?= getTextTranslate(90); ?></a>
				<a href="index.html" class="error__link error__link--right"> <?= getTextTranslate(91); ?>
					<span class="error__arrow">→</span>
				</a>
			</div>
		</div>
	</div>
	<script src="https://maps.googleapis.com/maps/api/js"></script>
	<script src="dist/script.js"></script>
</body>

</html>