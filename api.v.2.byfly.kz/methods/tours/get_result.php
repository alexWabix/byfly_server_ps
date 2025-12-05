<?php
$codes = explode(',', $_POST['code']);
$page = $_POST['page'];
$base_id = $_POST['base_id'];

$all_tours = [];
$min_progress = 100;
$total_tours = 0;
$total_hotels = 0;
$min_price = PHP_INT_MAX;
$max_price = 0;

$requests = [];

$comission_remove = false;
$commission_percent = 0;

if (empty($_POST['user_id']) == false) {
    $isAgent = isUserAgent($_POST['user_id']);
    if ($isAgent) {
        $comission_remove = true;
        $commission_percent = 6;
    } else {
        $comission_remove = false;
        $commission_percent = 0;
    }

} else {
    $comission_remove = false;
    $commission_percent = 0;
}



foreach ($codes as $code) {
    $query = [
        "authlogin" => $tourvisor_login,
        "authpass" => $tourvisor_password,
        "format" => "json",
        "requestid" => $code,
        "type" => "result",
        "onpage" => 40,
        "page" => $page,
        "nodescription" => 0, // Включаем описания отелей
    ];

    $url = 'https://tourvisor.ru/xml/result.php?' . http_build_query($query);
    $requests[$code] = $url;
}

$responses = multiRequest($requests);

foreach ($responses as $code => $data) {
    $datae = json_decode($data, true);

    if (!isset($datae['data']['status']['state']) || $datae['data']['status']['state'] == 'no search results') {
        continue;
    }

    $min_progress = min($min_progress, $datae['data']['status']['progress']);
    $total_tours += $datae['data']['status']['toursfound'];
    $total_hotels += $datae['data']['status']['hotelsfound'];
    $min_price = min($min_price, $datae['data']['status']['minprice']);
    $max_price = max($max_price, $datae['data']['status']['maxprice']);

    if (isset($datae['data']['result']['hotel'])) {
        foreach ($datae['data']['result']['hotel'] as &$hotel) {
            if ($comission_remove) {

                $price_without_commission = $hotel['price'] / (1 + ($commission_percent / 100));

                $hotel['price'] = round($price_without_commission);
                $newTour = array();
                foreach ($hotel['tours']['tour'] as $tour) {
                    $price_without_commission2 = $tour['price'] / (1 + ($commission_percent / 100));
                    $tour['price'] = round($price_without_commission2);
                    $newTour[] = $tour;
                }

                $hotel['tours']['tour'] = $newTour;
            }



            $all_tours[] = $hotel;
        }
    }
}

usort($all_tours, function ($a, $b) {
    return $a['price'] <=> $b['price'];
});

$db->query("UPDATE tours_searched SET 
    min_price='$min_price', 
    max_price='$max_price', 
    count_tours='$total_tours',
    count_hotels='$total_hotels' 
    WHERE id='$base_id'");

$response = [
    'type' => true,
    'data' => [
        'state' => [
            'progress' => $min_progress,
            "state" => $min_progress == 100 ? 'finished' : 'in progress',
            'toursfound' => $total_tours,
            'hotelsfound' => $total_hotels,
            'minprice' => $min_price,
            'maxprice' => $max_price,
        ],
        'data' => [
            'hotel' => $all_tours,
        ]
    ]
];

echo json_encode($response);

function multiRequest($urls)
{
    $mh = curl_multi_init();
    $curl_array = [];

    foreach ($urls as $key => $url) {
        $curl_array[$key] = curl_init($url);
        curl_setopt($curl_array[$key], CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($mh, $curl_array[$key]);
    }

    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $responses = [];
    foreach ($urls as $key => $url) {
        $responses[$key] = curl_multi_getcontent($curl_array[$key]);
        curl_multi_remove_handle($mh, $curl_array[$key]);
        curl_close($curl_array[$key]);
    }

    curl_multi_close($mh);
    return $responses;
}
