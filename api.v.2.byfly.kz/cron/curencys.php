<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

$currencyNames = [
    'AUD' => ['ru' => 'Австралийский доллар', 'kk' => 'Австралия доллары', 'en' => 'Australian Dollar'],
    'AZN' => ['ru' => 'Азербайджанский манат', 'kk' => 'Әзербайжан манаты', 'en' => 'Azerbaijani Manat'],
    'AMD' => ['ru' => 'Армянский драм', 'kk' => 'Армян драмы', 'en' => 'Armenian Dram'],
    'BYN' => ['ru' => 'Белорусский рубль', 'kk' => 'Беларус рублі', 'en' => 'Belarusian Ruble'],
    'BRL' => ['ru' => 'Бразильский реал', 'kk' => 'Бразилия реалдары', 'en' => 'Brazilian Real'],
    'HUF' => ['ru' => 'Венгерский форинт', 'kk' => 'Венгрия форинті', 'en' => 'Hungarian Forint'],
    'HKD' => ['ru' => 'Гонконгский доллар', 'kk' => 'Гонконг доллары', 'en' => 'Hong Kong Dollar'],
    'GEL' => ['ru' => 'Грузинский лари', 'kk' => 'Грузия лари', 'en' => 'Georgian Lari'],
    'DKK' => ['ru' => 'Датская крона', 'kk' => 'Дания кронасы', 'en' => 'Danish Krone'],
    'AED' => ['ru' => 'Дирхам ОАЭ', 'kk' => 'БАӘ дирхамы', 'en' => 'UAE Dirham'],
    'USD' => ['ru' => 'Доллар США', 'kk' => 'АҚШ доллары', 'en' => 'US Dollar'],
    'EUR' => ['ru' => 'Евро', 'kk' => 'Евро', 'en' => 'Euro'],
    'INR' => ['ru' => 'Индийская рупия', 'kk' => 'Үнді рупиясы', 'en' => 'Indian Rupee'],
    'IRR' => ['ru' => 'Иранский риал', 'kk' => 'Иран риалы', 'en' => 'Iranian Rial'],
    'CAD' => ['ru' => 'Канадский доллар', 'kk' => 'Канада доллары', 'en' => 'Canadian Dollar'],
    'CNY' => ['ru' => 'Китайский юань', 'kk' => 'Қытай юані', 'en' => 'Chinese Yuan'],
    'KWD' => ['ru' => 'Кувейтский динар', 'kk' => 'Кувейт динары', 'en' => 'Kuwaiti Dinar'],
    'KGS' => ['ru' => 'Киргизский сом', 'kk' => 'Қырғыз сомы', 'en' => 'Kyrgyz Som'],
    'MYR' => ['ru' => 'Малайзийский ринггит', 'kk' => 'Малайзия ринггиті', 'en' => 'Malaysian Ringgit'],
    'MXN' => ['ru' => 'Мексиканский песо', 'kk' => 'Мексика песосы', 'en' => 'Mexican Peso'],
    'MDL' => ['ru' => 'Молдавский лей', 'kk' => 'Молдова лейі', 'en' => 'Moldovan Leu'],
    'NOK' => ['ru' => 'Норвежская крона', 'kk' => 'Норвегия кронасы', 'en' => 'Norwegian Krone'],
    'PLN' => ['ru' => 'Польский злотый', 'kk' => 'Польша злотыйы', 'en' => 'Polish Zloty'],
    'SAR' => ['ru' => 'Саудовский риял', 'kk' => 'Сауд Арабия риялы', 'en' => 'Saudi Riyal'],
    'RUB' => ['ru' => 'Российский рубль', 'kk' => 'Ресей рублі', 'en' => 'Russian Ruble'],
    'XDR' => ['ru' => 'СПЗ (специальные права заимствования)', 'kk' => 'СДҚ (арнайы қарыз алу құқықтары)', 'en' => 'SDR (Special Drawing Rights)'],
    'SGD' => ['ru' => 'Сингапурский доллар', 'kk' => 'Сингапур доллары', 'en' => 'Singapore Dollar'],
    'TJS' => ['ru' => 'Таджикский сомони', 'kk' => 'Тәжік сомониі', 'en' => 'Tajikistani Somoni'],
    'THB' => ['ru' => 'Тайский бат', 'kk' => 'Тайланд баты', 'en' => 'Thai Baht'],
    'TRY' => ['ru' => 'Турецкая лира', 'kk' => 'Түркия лирасы', 'en' => 'Turkish Lira'],
    'UZS' => ['ru' => 'Узбекский сум', 'kk' => 'Өзбек сомы', 'en' => 'Uzbek Som'],
    'UAH' => ['ru' => 'Украинская гривна', 'kk' => 'Украина гривнасы', 'en' => 'Ukrainian Hryvnia'],
    'GBP' => ['ru' => 'Фунт стерлингов', 'kk' => 'Фунт стерлинг', 'en' => 'Pound Sterling'],
    'CZK' => ['ru' => 'Чешская крона', 'kk' => 'Чехия кронасы', 'en' => 'Czech Koruna'],
    'SEK' => ['ru' => 'Шведская крона', 'kk' => 'Швеция кронасы', 'en' => 'Swedish Krona'],
    'CHF' => ['ru' => 'Швейцарский франк', 'kk' => 'Швейцар франкы', 'en' => 'Swiss Franc'],
    'ZAR' => ['ru' => 'Южноафриканский рэнд', 'kk' => 'Оңтүстік Африка рэнді', 'en' => 'South African Rand'],
    'KRW' => ['ru' => 'Южнокорейская она', 'kk' => 'Оңтүстік Корея вонасы', 'en' => 'South Korean Won'],
    'JPY' => ['ru' => 'Японская иена', 'kk' => 'Жапония иенасы', 'en' => 'Japanese Yen'],
];


$xml = simplexml_load_file('https://nationalbank.kz/rss/rates_all.xml');

foreach ($xml->channel->item as $item) {
    $currencyCode = strtoupper((string) $item->title);
    $rate = (float) $item->description;

    if (isset($currencyNames[$currencyCode])) {
        $currencyNameRu = $currencyNames[$currencyCode]['ru'];
        $currencyNameKk = $currencyNames[$currencyCode]['kk'];
        $currencyNameEn = $currencyNames[$currencyCode]['en'];
    } else {
        $currencyNameRu = 'Неизвестная валюта';
        $currencyNameKk = 'Белгісіз валюта';
        $currencyNameEn = 'Unknown Currency';
        echo "Не найдено в массиве для кода: $currencyCode\n";
    }

    $query = "SELECT id FROM exchange_rates WHERE currency_code = '$currencyCode'";
    $result = $db->query($query);

    if ($result->num_rows > 0) {
        $updateQuery = "UPDATE exchange_rates SET 
                            currency_name_ru = '$currencyNameRu',
                            currency_name_kk = '$currencyNameKk',
                            currency_name_en = '$currencyNameEn',
                            rate = $rate
                        WHERE currency_code = '$currencyCode'";
        $db->query($updateQuery);
    } else {
        $insertQuery = "INSERT INTO exchange_rates (currency_code, currency_name_ru, currency_name_kk, currency_name_en, rate, priority_stay) 
                        VALUES ('$currencyCode', '$currencyNameRu', '$currencyNameKk', '$currencyNameEn', $rate, '0')";
        $db->query($insertQuery);
    }
}

$db->close();
?>