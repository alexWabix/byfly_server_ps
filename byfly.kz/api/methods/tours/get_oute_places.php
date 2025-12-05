<?php
    if(empty($_POST['gds'])){
        $_POST['gds'] = 0;
    }

    function getListCitys($country, $gds){
        global $toursBase;
        $city = array();
        if($gds == 0){
            $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$country."' AND gds = '0' GROUP BY city_oute");
        }else if($gds == 1){
            $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$country."' GROUP BY city_oute");
        }else{
            $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$country."' AND gds = '1' GROUP BY city_oute");
        }
       
        while($ls = $listCitys->fetch_assoc()){
            $city[] = array(
                "name"=>$ls['city_oute']
            );
        }
        return $city;
    }

    function getCountryInfo($country){
        global $toursBase;
        global $domain;
        $infoCountryDB = $toursBase->query("SELECT * FROM countries WHERE title='".$country."' ORDER BY id ASC LIMIT 1");
        if($infoCountryDB -> num_rows > 0){
            $infoCountryDB = $infoCountryDB->fetch_assoc();
            $info['flag'] = $domain.$infoCountryDB['icon'];
            $info['latName'] = $infoCountryDB['eng_title'];
            $info['id'] = $infoCountryDB['id'];
            return $info;
        }else{
            return null;
        }
    }


    $resp = array();
    if($_POST['gds'] == 0){
        $listCountryOute = $toursBase->query("SELECT * FROM tour_params WHERE gds = '0' GROUP BY country_oute");
    }else if($_POST['gds'] == 1){
        $listCountryOute = $toursBase->query("SELECT * FROM tour_params GROUP BY country_oute");
    }else{
        $listCountryOute = $toursBase->query("SELECT * FROM tour_params WHERE gds = '1' GROUP BY country_oute");
    }

    $countryInfo = getCountryInfo('Казахстан');
    $resp[] = array(
        "id"=>$countryInfo['id'],
        "name"=>'Казахстан',
        "flag" => $countryInfo['flag'],
        "country_info"=>$countryInfo,
        "citys"=>getListCitys('Казахстан', $_POST['gds'])
    );
    $countryInfo = getCountryInfo('Россия');
    $resp[] = array(
        "id"=>$countryInfo['id'],
        "name"=>'Россия',
        "flag" => $countryInfo['flag'],
        "country_info"=>$countryInfo,
        "citys"=>getListCitys('Россия', $_POST['gds'])
    );
    while($ct = $listCountryOute->fetch_assoc()){
        if($ct['country_oute'] != 'Казахстан' and $ct['country_oute'] != 'Россия'){
            $countryInfo = getCountryInfo($ct['country_oute']);
            $resp[] = array(
                "id"=>$countryInfo['id'],
                "name" => $ct['country_oute'],
                "flag" => $countryInfo['flag'],
                "country_info"=>$countryInfo,
                "citys" => getListCitys($ct['country_oute'], $_POST['gds'])
            );
        }
    }

    responceApi($type = true, $message = 'SUCCES BUILD LIST PLACES OUTE...', 0, $resp);
?>