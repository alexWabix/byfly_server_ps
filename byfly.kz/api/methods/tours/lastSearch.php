<?php
    include('plugins/search_hotel/index.php');
    $where = '';
    if(empty($_POST['countryOute']) == false){
        $where .= ' AND countryOute="'.$_POST['countryOute'].'"';
    }

    if(empty($_POST['cityOute']) == false){
        $where .= ' AND cityOute="'.$_POST['cityOute'].'"';
    }


    if(empty($_POST['countryTo']) == false){
        $where .= ' AND countryTo="'.$_POST['countryTo'].'"';
    }

    if(empty($_POST['cityTo']) == false){
        $where .= ' AND regionTo="'.$_POST['cityTo'].'"';
    }

    $tours = array();
    $keysID = array();
    $sql = "SELECT * FROM search_keys WHERE date_create > '".date('Y-m-d')."01:00:00' ".$where.' ORDER BY id DESC LIMIT 10';
    $lastToursDB = $toursBase->query($sql);
    while($lastTours = $lastToursDB->fetch_assoc()){
        $keysID[] = $lastTours['id'];
    }

    $whereTours = '';
    foreach ($keysID as $keyId) {
        if(empty($whereTours)){
            $whereTours .= 'WHERE id_search="'.$keyId.'"';
        }else{
            $whereTours .= ' OR id_search="'.$keyId.'"';
        }
    }

    $resp = array();
    if(count($keysID) > 0){
        $sql2 = "SELECT * FROM search_results ".$whereTours.' GROUP BY hotel_name ORDER BY price ASC LIMIT 10';
        $dbTours = $toursBase->query($sql2);
        while($tt = $dbTours->fetch_assoc()){
            $keyInfo = $toursBase->query("SELECT * FROM search_keys WHERE id='".$tt['id_search']."'")->fetch_assoc();
            if($tt['hotel_id'] > 0){
                $hotelInfo = getHotelInfo($tt['hotel_id']);
            }else{
                $hotelInfo = searchHotel($tt['hotel_name'], $keyInfo['countryTo']);
            }
            if($hotelInfo['type'] == 'succ'){
                $tt['price'] = $tt['price_shop'];
                $hotelInfo = $hotelInfo['hotel'];
                $tt['hotel_info'] = $hotelInfo;
                $tt['countryTo'] = $keyInfo['countryTo'];
                $tt['cityTo'] = $keyInfo['regionTo'];
                $tt['countryOut'] = $keyInfo['countryOute'];
                $tt['cityOut'] = $keyInfo['cityOute'];
                $tt['adults'] = $keyInfo['countAdalt'];
                $tt['children'] = empty($keyInfo['childrems']) ? 0 : count(explode(',', $keyInfo['childrems']));
                $resp[] = $tt;
            }else{
                $hotelInfo = null;
            }
            
        }
    }
    

    responceApi($type = true, $message = 'SUCCESS! List tours generate...', 0, $resp);
?>