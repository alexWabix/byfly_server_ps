<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['toursId']) == false){
        $getToursInfoDB = $toursBase->query("SELECT * FROM search_results WHERE id='".$_POST['toursId']."'");
        if($getToursInfoDB -> num_rows > 0){
            $tourInfo = $getToursInfoDB->fetch_assoc();
            $tourInfo['search_info'] = $toursBase->query("SELECT * FROM search_keys WHERE id='".$tourInfo['id_search']."'")->fetch_assoc();
            $tourInfo['price'] = $toursInfo['price_shop'];

            if($tourInfo['hotel_id'] > 0){
                $search_hotel = getHotelInfo($tourInfo['hotel_id']);
                if($search_hotel['type'] == 'succ'){
                    $tourInfo['hotel'] = $search_hotel['hotel'];
                }else{
                    $tourInfo['hotel'] = null;
                }
            }else{
                $search_hotel = searchHotel($tourInfo['hotel_name'], $tourInfo['country_to']);
                if($search_hotel['type'] == 'succ'){
                    $tourInfo['hotel'] = $search_hotel['hotel'];
                }else{
                    $tourInfo['hotel'] = null;
                }
            }
           
            responceApi($type = true, $message = 'SUCCESS! List place to generate...', 0, $tourInfo);
        }else{
            $getToursInfoDB = $toursBase->query("SELECT * FROM search_results_arhive WHERE id='".$_POST['toursId']."'");
            if($getToursInfoDB -> num_rows > 0){
                $tourInfo = $getToursInfoDB->fetch_assoc();
                $tourInfo['price'] = $toursInfo['price_shop'];
                
                $tourInfo['search_info'] = $toursBase->query("SELECT * FROM search_keys_arhive WHERE id='".$tourInfo['id_search']."'")->fetch_assoc();
    
                if($tourInfo['hotel_id'] > 0){
                    $search_hotel = getHotelInfo($tourInfo['hotel_id']);
                    if($search_hotel['type'] == 'succ'){
                        $tourInfo['hotel'] = $search_hotel['hotel'];
                        $tourInfo['hotel']['title'] = $tourInfo['hotel']['title'].'1';
                    }else{
                        $tourInfo['hotel'] = null;
                    }
                }else{
                    $search_hotel = searchHotel($tourInfo['hotel_name'], $tourInfo['country_to']);
                    if($search_hotel['type'] == 'succ'){
                        $tourInfo['hotel'] = $search_hotel['hotel'];
                        $tourInfo['hotel']['title'] = $tourInfo['hotel']['title'].'1';
                    }else{
                        $tourInfo['hotel'] = null;
                    }
                }
               
                responceApi($type = true, $message = 'SUCCESS! List place to generate...', 0, $tourInfo);
            }else{
                responceApi($type = false, $message = 'Empty this tour '.$_POST['toursId'], 0,);
            }
        }
    }else{
        responceApi($type = false, $message = 'Not send id tours', 0,);
    }
?>