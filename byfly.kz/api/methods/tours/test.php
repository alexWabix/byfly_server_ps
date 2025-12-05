<?php
    $link = 'https://search.tez-tour.com/tariffsearch/getResult?tourId=5734&countryId=5732&cityId=2707&priceMin=0&priceMax=999999&currency=122196&nightsMin=6&nightsMax=12&hotelClassId=2567&accommodationId=2&formatResult=false&tourType=1&groupByHotel=2&locale=ru&after=10.05.2023&before=25.05.2023&hotelClassBetter=true&rAndBBetter=true&hotelInStop=false&specialInStop=false&noTicketsTo=false&noTicketsFrom=false&promoFlag=true&version=2&searchTypeId=6&birthdays=';

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $link,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY => '195.80.48.50:8000',
        CURLOPT_PROXYUSERPWD => 'XqVcRE:2GxJvV',
        CURLOPT_FAILONERROR => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_REFERER => 'tez-tour.com',
        CURLOPT_USERAGENT => $agent,
    ));

    $result = curl_exec($ch);
    if(curl_errno($ch)) {
        echo 'Ошибка curl: ' . curl_error($ch);
    }

    curl_close($ch);
    echo $result;
?>