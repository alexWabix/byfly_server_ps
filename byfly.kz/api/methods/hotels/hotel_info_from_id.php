<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['hotelID']) == false){
        $resp = array();
        $hotelInfo = getHotelInfo($_POST['hotelID']);   

        if($hotelInfo['type'] == 'succ'){
            $resp['info'] = $hotelInfo['hotel'];
            
            $resp['info']['minPrice'] = 1000000000000000;


            $toursDBArchive = $toursBase->query("SELECT * FROM search_results_arhive WHERE hotel_name='".$resp['info']['title']."' AND city_oute LIKE '".$_POST['selectCitys']."' ORDER BY price ASC LIMIT 1");
            if($toursDBArchive->num_rows > 0){
                $toursDBArchive = $toursDBArchive->fetch_assoc();
                if($resp['info']['minPrice'] < $toursDBArchive['minPrice']){
                    $resp['info']['minPrice'] = $toursDBArchive['price'];
                }
            }

            
            $keys = array();
            $resp['info']['search_tours'] = array();
            $toursDB = $toursBase->query("SELECT * FROM search_results WHERE hotel_name='".$resp['info']['title']."' AND city_oute LIKE '".$_POST['selectCitys']."' ORDER BY price ASC");
            while($tours = $toursDB->fetch_assoc()){
                if(empty($keys[$tours['id_search']])){
                    $keys[$tours['id_search']] = $toursBase->query("SELECT * FROM search_keys WHERE id='".$tours['id_search']."'")->fetch_assoc();
                    $keys[$tours['id_search']]['childrems'] = empty($keys[$tours['id_search']]['childrems']) ? array() : explode(',', $keys[$tours['id_search']]['childrems']);
                }
                $tours['info_key'] = $keys[$tours['id_search']];
                $resp['info']['search_tours'][] = $tours;
                if($tours['price'] < $resp['info']['minPrice']){
                    $resp['info']['minPrice'] = $tours['price'];
                }
            }

            if($resp['info']['minPrice'] == 1000000000000000){
                $resp['info']['minPrice'] = 0;
            }



            responceApi(true, "List tours generated for page 1.", 0, $resp);
        }else{
            responceApi(false, "Not finde this hotel. Please check your data.", 0);
        }
    }else{
        responceApi(false, "You didn't provide a tour search ID to display results.", 0);
    }
?>