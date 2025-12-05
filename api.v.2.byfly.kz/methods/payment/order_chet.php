<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

function num2str($num)
{
    $units = [
        ['ТЕНГЕ', 'ТЕНГЕ', 'ТЕНГЕ'], // Названия тенге в разных формах
        ['ТИЫН', 'ТИЫН', 'ТИЫН'], // Названия тиын в разных формах
    ];

    $tens = [
        2 => 'ДВАДЦАТЬ',
        3 => 'ТРИДЦАТЬ',
        4 => 'СОРОК',
        5 => 'ПЯТЬДЕСЯТ',
        6 => 'ШЕСТЬДЕСЯТ',
        7 => 'СЕМЬДЕСЯТ',
        8 => 'ВОСЕМЬДЕСЯТ',
        9 => 'ДЕВЯНОСТО'
    ];

    $hundreds = [
        1 => 'СТО',
        2 => 'ДВЕСТИ',
        3 => 'ТРИСТА',
        4 => 'ЧЕТЫРЕСТА',
        5 => 'ПЯТЬСОТ',
        6 => 'ШЕСТЬСОТ',
        7 => 'СЕМЬСОТ',
        8 => 'ВОСЕМЬСОТ',
        9 => 'ДЕВЯТЬСОТ'
    ];

    $ones = [
        1 => 'ОДНА', // женский род для тысяч
        2 => 'ДВЕ',  // женский род для тысяч
        3 => 'ТРИ',
        4 => 'ЧЕТЫРЕ',
        5 => 'ПЯТЬ',
        6 => 'ШЕСТЬ',
        7 => 'СЕМЬ',
        8 => 'ВОСЕМЬ',
        9 => 'ДЕВЯТЬ'
    ];

    $ones_tens = [
        10 => 'ДЕСЯТЬ',
        11 => 'ОДИННАДЦАТЬ',
        12 => 'ДВЕНАДЦАТЬ',
        13 => 'ТРИНАДЦАТЬ',
        14 => 'ЧЕТЫРНАДЦАТЬ',
        15 => 'ПЯТНАДЦАТЬ',
        16 => 'ШЕСТНАДЦАТЬ',
        17 => 'СЕМНАДЦАТЬ',
        18 => 'ВОСЕМНАДЦАТЬ',
        19 => 'ДЕВЯТНАДЦАТЬ'
    ];

    $parts = explode('.', number_format($num, 2, '.', ''));

    $rub = intval($parts[0]); // Целая часть - тенге
    $kop = intval($parts[1]); // Дробная часть - тиыны

    if ($rub == 0) {
        $rub_result = 'НОЛЬ ' . $units[0][2];
    } else {
        $rub_result = convert_large_number($rub, $ones, $tens, $hundreds, $ones_tens, $units[0]);
    }

    if ($kop == 0) {
        $kop_result = 'НОЛЬ ' . $units[1][2];
    } else {
        $kop_result = convert_part($kop, $ones, $tens, $hundreds, $ones_tens, $units[1]);
    }

    return mb_strtoupper($rub_result) . ' ' . mb_strtoupper($kop_result);
}

function convert_large_number($num, $ones, $tens, $hundreds, $ones_tens, $unit)
{
    $thousands = floor($num / 1000);
    $remainder = $num % 1000;

    $result = '';
    if ($thousands > 0) {
        $result .= convert_part($thousands, $ones, $tens, $hundreds, $ones_tens, ['ТЫСЯЧА', 'ТЫСЯЧИ', 'ТЫСЯЧ']) . ' ';
    }

    if ($remainder > 0) {
        $result .= convert_part($remainder, $ones, $tens, $hundreds, $ones_tens, $unit);
    }

    return trim($result);
}

function convert_part($num, $ones, $tens, $hundreds, $ones_tens, $unit)
{
    $result = '';
    $num = str_pad($num, 3, '0', STR_PAD_LEFT);

    $hundred = intval($num[0]);
    $ten = intval($num[1]);
    $one = intval($num[2]);

    if ($hundred > 0) {
        $result .= $hundreds[$hundred] . ' ';
    }

    if ($ten > 1) {
        $result .= $tens[$ten] . ' ';
        if ($one > 0) {
            $result .= $ones[$one] . ' ';
        }
    } elseif ($ten == 1) {
        $result .= $ones_tens[$ten * 10 + $one] . ' ';
    } else {
        if ($one > 0) {
            $result .= $ones[$one] . ' ';
        }
    }

    $result .= get_form($num, $unit);

    return trim($result);
}

function get_form($num, $forms)
{
    $num = intval($num);
    if ($num % 100 >= 11 && $num % 100 <= 19) {
        return $forms[2];
    }

    switch ($num % 10) {
        case 1:
            return $forms[0];
        case 2:
        case 3:
        case 4:
            return $forms[1];
        default:
            return $forms[2];
    }
}
function split_number_by_digits($number)
{
    $number = strval($number);
    $reversed_number = strrev($number);
    $chunks = str_split($reversed_number, 3);
    $result = array_map('strrev', $chunks);
    return implode(' ', array_reverse($result));
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title> Счет на оплату №<?= $_GET['number']; ?></title>
    <style>
        p {
            line-height: 1;
        }
    </style>
</head>

<body>
    <div>
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 24.9377%;"> <img height="39"
                            src="https://lh7-rt.googleusercontent.com/docsz/AD_4nXfK06yR6U9t7s12FpeY3L4kN7823MdaLJT2vZd2G6ggjEDPWMZZL6I1H6J2br6Jl2mcpMRj5d8e3wblPNGDvlYbTCPN0_O9BMPk2e4jWg_EqYB-Rhfl5n4qOeF7ORd5zm4zc-4ndgHkg20AbaQg6JdPaQb3?key=iabW-sQ0HIiMiuW2S-pwkA"
                            width="154" /> </td>
                    <td style="width: 74.9338%;">
                        <p style="text-align: justify;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                Внимание! Оплата Данного счета означает
                                согласие с условиями оказанием услуг.
                                Уведомление об оплате обязательно, в
                                противном случае не гарантируется оказание
                                услуг согласно ваучера. Услуга оказывается
                                только по факту поступления денег на р/c
                                Поставщика. </span> </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <p>
        <strong>
            <span style="font-size:10pt;font-family:Arial,sans-serif;">
                Образец платежного поручения
            </span>
        </strong>
    <div>
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tbody>
                <tr>
                    <td colspan="15"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); border-right: 0.625pt solid rgb(0, 0, 0); border-top: 0.625pt solid rgb(0, 0, 0); width: 44.7943%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                Бенефициар: </span> </strong>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); text-align: center; border-right: 0.625pt solid rgb(0, 0, 0); border-top: 0.625pt solid rgb(0, 0, 0); width: 31.5733%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                ИИК </span> </strong>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0);text-align: center;border-right: 0.625pt solid rgb(0, 0, 0); border-top: 0.625pt solid rgb(0, 0, 0); width: 23.5681%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                Кбе </span> </strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="15"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); border-right: 0.625pt solid rgb(0, 0, 0); width: 44.7943%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                Товарищество с ограниченной
                                ответственностью "ByFly" </span>
                        </strong>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); text-align: center; border-right: 0.625pt solid rgb(0, 0, 0); width: 31.5733%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                <?= $_GET['my_iik']; ?> </span>
                        </strong>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); text-align: center; border-right: 0.625pt solid rgb(0, 0, 0); width: 23.5681%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                17 </span> </strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="13"
                        style="border-left: 0.625pt solid rgb(0, 0, 0);  border-right: 0.625pt solid rgb(0, 0, 0); border-bottom: 0.625pt solid rgb(0, 0, 0); width: 44.7943%;">
                        <span style="font-size:9pt;font-family:Arial,sans-serif;">
                            БИН: 231040040048 </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="15"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); border-right: 0.625pt solid rgb(0, 0, 0); border-top: 0.625pt solid rgb(0, 0, 0); width: 44.7943%;">
                        <span style="font-size:9pt;font-family:Arial,sans-serif;">
                            Банк бенефициара: </span>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); text-align: center; border-right: 0.625pt solid rgb(0, 0, 0); border-top: 0.625pt solid rgb(0, 0, 0); width: 31.5553%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                БИК </span> </strong>
                    </td>
                    <td colspan="6"
                        style="border-left:solid #000000 0.625pt;text-align: center;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                Код назначения платежа </span>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="15"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); border-right: 0.625pt solid rgb(0, 0, 0); border-bottom: 0.625pt solid rgb(0, 0, 0); width: 44.7943%;">
                        <span style="font-size:9pt;font-family:Arial,sans-serif;">
                            <?= $_GET['my_bank_name']; ?> </span>
                    </td>
                    <td colspan="6"
                        style="border-left: 0.625pt solid rgb(0, 0, 0); text-align: center; border-right: 0.625pt solid rgb(0, 0, 0); border-bottom: 0.625pt solid rgb(0, 0, 0); width: 31.5553%;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                <?= $_GET['my_bank_bik']; ?> </span> </strong>
                    </td>
                    <td colspan="6"
                        style="border-left:solid #000000 0.625pt;text-align: center; border-right:solid #000000 0.625pt;border-bottom:solid #000000 0.625pt;">
                        <strong> <span style="font-size:9pt;font-family:Arial,sans-serif;">
                                871 </span> </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <p></p>
    <br><br>
    <strong> <span style="font-size:13.999999999999998pt;font-family:Arial,sans-serif;">
            Счет на оплату </span> </strong> <strong> <span
            style="font-size:13.999999999999998pt;font-family:Arial,sans-serif;">
        </span> </strong> <strong> <span style="font-size:13.999999999999998pt;font-family:Arial,sans-serif;">
            №<?= $_GET['number']; ?> от <?= $_GET['date_schet']; ?> </span>
    </strong>
    <div>
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tbody>
                <tr>
                    <td colspan="27" style="border-bottom:solid #b0b0b0 1.25pt;">

                    </td>
                </tr>

                <tr>
                    <td colspan="27">
                        <strong> <br /><span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                БИН / ИИН 231040040048,Товарищество
                                с
                                ограниченной ответственностью
                                "ByFly",<?= $_GET['my_adres']; ?> </span>
                        </strong>
                    </td>
                </tr>

                <tr>
                    <td colspan="27">
                        <br>
                        <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                            </span> </strong> <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                БИН / ИИН </span> </strong> <strong>
                            <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                            </span> </strong> <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                <?= $_GET['company_bin']; ?> </span> </strong>

                        <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                            </span> </strong> <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                <?= $_GET['company_too']; ?> </span> </strong> <strong>
                            <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                , </span> </strong> <strong> <span
                                style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                <?= $_GET['company_adress']; ?></span>
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div>
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tbody>
                <tr>
                    <td colspan="27">
                        <p> <strong> <span style="font-size:10pt;font-family:Arial,sans-serif;">
                                    Договор оферта на туристическое обслуживание</span>
                            </strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td
                        style="border-left:solid #000000 1.25pt;border-right:solid #000000 0.625pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: center;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    № </span> </strong> </p>
                    </td>
                    <td colspan="2"
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: left;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Наименование </span> </strong> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: center;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Кол-во </span> </strong> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: center;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Ед. </span> </strong> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: center;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Цена </span> </strong> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 1.25pt;border-top:solid #000000 1.25pt;border-bottom:solid #000000 0.625pt;">
                        <p style="text-align: center;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Сумма </span> </strong> </p>
                    </td>
                </tr>
                <tr>
                    <td
                        style="border-left:solid #000000 1.25pt;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                1 </span> </p>
                    </td>
                    <td colspan="2"
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: left;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                <?= $_GET['order_info']; ?> </span> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                1,000 </span> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                шт </span> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 0.625pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                <?= split_number_by_digits($_GET['order_price']); ?> KZT</span> </p>
                    </td>
                    <td
                        style="border-left:solid #000000 0.625pt;border-right:solid #000000 1.25pt;border-top:solid #000000 0.625pt;border-bottom:solid #000000 1.25pt;">
                        <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                <?= split_number_by_digits($_GET['order_price']); ?> KZT</span> </p>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:solid #000000 1.25pt;"> <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;"> <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;"> <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 1.25pt;"> <br />
                    </td>
                </tr>
                <tr>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td>
                        <p style="text-align: right;"> <strong> <span
                                    style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                    Итого: </span> </strong> </p>
                    </td>
                    <td>
                        <p style="text-align: right;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
                                <?= split_number_by_digits($_GET['order_price']); ?> KZT</span> </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <p> <br /> </p>
    <div>
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tbody>
                <tr>
                    <td colspan="32">
                        <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                            Всего наименований 1, на сумму <?= split_number_by_digits($_GET['order_price']); ?> KZT
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="31">
                        <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                Всего к оплате: <?= num2str($_GET['order_price']); ?> </span> </strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="height: 10px;"></div>
                    </td>
                </tr>

                <tr>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">

                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                    <td style="border-top:solid #000000 1.25pt;">
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                        <div style="height: 10px;"></div>
                        <strong> <span style="font-size:9.5pt;font-family:Arial,sans-serif;">
                                Исполнитель </span> </strong>
                    </td>
                    <td colspan="14" style="border-bottom:solid #000000 0.625pt;">
                        <div style="height: 10px;"></div>
                    </td>
                    <td colspan="13">
                        <div style="height: 10px;"></div>
                        <span style="font-size:8pt;font-family:Arial,sans-serif;">
                            /Абдалиева Гульшат Бегалиевна/ </span>
                    </td>
                </tr>
                <tr>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br>
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <br />
                    </td>
                    <td style="border-top:solid #000000 0.625pt;">
                        <div style="margin-top: -130px; margin-right: -100px;">
                            <img width="200px" src="https://api.v.2.byfly.kz/images/pechat_rospis.png">
                        </div>
                    </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                    <td> <br /> </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p> <br /> </p>
    <p> <br /> </p>
    <p style="text-align: center;"> <span style="font-size:8pt;font-family:Arial,sans-serif;">
            Внимание!
            Оплата Данного счета означает согласие с условиями оказанием
            услуг. Уведомление об оплате обязательно, в противном случае
            не
            гарантируется оказание услуг согласно ваучера. Услуга
            оказывается только по факту поступления денег на р/c
            Поставщика
        </span> </p>
</body>

</html>