<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['tour_id']) == false){
        $getToursInfoDB = $toursBase->query("SELECT * FROM search_results WHERE id='".$_POST['tour_id']."'");
        if($getToursInfoDB -> num_rows > 0){
            $tourInfo = $getToursInfoDB->fetch_assoc();
            $tourInfo['search_info'] = $toursBase->query("SELECT * FROM search_keys WHERE id='".$tourInfo['id_search']."'")->fetch_assoc();

            $tourInfo['operator_info'] = $toursBase->query("SELECT * FROM sites WHERE id='".$tourInfo['operator_id']."'")->fetch_assoc();

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
            $tourInfo['price'] = $tourInfo['price_shop'];
           
            responceApi($type = true, $message = 'SUCCESS! List place to generate...', 0, $tourInfo);
        }else{
            responceApi($type = false, $message = 'Empty this tour', 0,);
        }
    }else{
        responceApi($type = false, $message = 'Not send id tours', 0,);
    }
?>