<?php
    if(empty($_POST['country_oute']) == false){
        if(empty($_POST['city_oute']) == false){
            if(empty($_POST['gds'])){
                $_POST['gds'] = 0;
            }
            $arrayTo = array();
            if($_POST['gds'] == 0){
                $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['country_oute']."' AND city_oute = '".$_POST['city_oute']."' AND gds='0' GROUP BY country_to");
            }else if($_POST['gds'] == 1){
                $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['country_oute']."' AND city_oute = '".$_POST['city_oute']."' GROUP BY country_to");
            }else{
                $listCitys = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['country_oute']."' AND city_oute = '".$_POST['city_oute']."' AND gds='1' GROUP BY country_to");
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
        
            while($ls = $listCitys->fetch_assoc()){
                $countryInfo = getCountryInfo($ls['country_to']);
                $arratCt = array(
                    "id"=>$countryInfo['id'],
                    "name"=>$ls['country_to'], 
                    "latName"=>$countryInfo['latName'],
                    "flag"=>$countryInfo['flag'],
                    "citys"=>array(),
                );
                $listRedions = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['country_oute']."' AND city_oute = '".$_POST['city_oute']."' AND country_to='".$ls['country_to']."' AND gds='0' GROUP BY region_to");
        
                $arratCt['citys'][] = array(
                    "name"=>'Все'
                );
                while($rg = $listRedions->fetch_assoc()){
                    if($rg['region_to'] != $ls['country_to']){
                        $arratCt['citys'][] = array(
                            "name"=>$rg['region_to']
                        );
                    }
                }
                $arrayTo[] = $arratCt;
            }

            responceApi($type = true, $message = 'SUCCESS! List place to generate...', 0, $arrayTo);
        }else{
            responceApi($type = false, $message = 'Not send name is city oute', 0,);
        }
    }else{
        responceApi($type = false, $message = 'Not send name is country oute', 0,);
    }
?>