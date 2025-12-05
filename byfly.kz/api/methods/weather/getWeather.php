<?php
    function getCoordinatesByCity($city) {
        $apiKey = 'f9b235f9-f095-4f1f-a664-ef54e6cac468';
        $url = "https://geocode-maps.yandex.ru/1.x/?apikey={$apiKey}&format=json&geocode=" . urlencode($city);
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        if ($data && isset($data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'])) {
            $coordinates = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
            list($longitude, $latitude) = explode(' ', $coordinates);
            return array(
                'latitude' => $latitude,
                'longitude' => $longitude
            );
        }
        
        return null;
    }

    
    function getWeatherForecastByCity($city) {
        $coordinates = getCoordinatesByCity($city);
        if($coordinates != null){
            $apiKey = '904d3d0c-19c7-4c84-bf7a-9ff2abcf7276';
            $url = "https://api.weather.yandex.ru/v2/forecast?lat=".$coordinates['latitude']."&lon=".$coordinates['longitude'];
            $headers = array( 
                'X-Yandex-API-Key: ' . $apiKey,
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return array(
                    'succ'=>false,
                    'mess'=>'Codes '.$httpCode
                );
            }
            
            $data = json_decode($response, true);
            if (!isset($data['forecasts'])) {
                return array(
                    'succ'=>false,
                    'mess'=>'Isset data'
                );
            }
            $forecasts = $data['forecasts'];
            
            return array(
                'succ'=>true,
                'data'=>$forecasts,
                'url'=>$data['info']['url']
            );
        }else{
            return array(
                'succ'=>false,
                'mess'=>'Isset coordinates object'
            );
        }
    }
    if(empty($_POST['cityTo']) == false){
        $weather = getWeatherForecastByCity($_POST['cityTo']);
        if($weather['succ']){
            responceApi($type = true, $message = 'Sended!', 0, array(
                'city'=>$_POST['cityTo'],
                'startDate'=>$_POST['startDate'],
                'endDate'=>$_POST['endDate'], 
                'weatherForecasts' => $weather['data'],
                'url'=>$weather['url']
            ));
        }else{
            responceApi($type = false, $weather['mess'], 0);
        }
        
    }else{
        responceApi($type = false, $message = 'Not send name is city', 0);
    }
?>