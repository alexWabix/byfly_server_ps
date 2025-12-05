<?php

    function addHotelForRealTimeParse($hotelName, $country){
        global $toursBase;
        $searchRealTimeParce = $toursBase->query("SELECT * FROM real_time_parse_hotel WHERE name_hotel LIKE '".$hotelName."' AND country_to LIKE '".$country."'");
        if($searchRealTimeParce->num_rows == 0){
            $toursBase->query("INSERT INTO real_time_parse_hotel (`id`, `name_hotel`, `country_to`, `date_create`, `status`, `booking_link`, `top_hotel_link`, `booking_page`, `top_hotel_info`, `count_error_insert_base`, `last_error_insert_base`, `count_error_get_links`, `last_error_get_links`, `count_error_booking_parse`, `last_error_booking_parse`, `last_parse_top_hotel_error`) VALUES (NULL, '".$hotelName."', '".$country."', CURRENT_TIMESTAMP, '0', '', '', '', '', '0', '', '0', '', '0', '', '');");
        }
    } 

    function noCashFindeHotel($hotelName, $country){
        global $toursBase;
        $conn = $toursBase;
        $hotelNameForLake = preg_replace('/\s\d\*$/', '', str_replace('_', ' ', $hotelName));
        $alterSearch = str_ireplace('aqua park','',strtolower($hotelNameForLake ));
        $alterSearch2 = str_ireplace('aquapark','aqua park',strtolower($hotelNameForLake));
        $alterSearch3 = str_ireplace(' resort','',strtolower($hotelNameForLake));

        $searchHotel = $toursBase->query("SELECT * FROM `hotels` WHERE country_name LIKE '".$country."' AND (
            `name` LIKE '%".$hotelNameForLake."%' OR 
            `name` LIKE '%".$alterSearch."%' OR 
            `name` LIKE '%".$alterSearch2."%' OR 
            `name` LIKE '%".$alterSearch3."%' OR
            `key_hotel` LIKE '%".$hotelNameForLake."%' OR 
            `key_hotel` LIKE '%".$alterSearch."%' OR 
            `key_hotel` LIKE '%".$alterSearch2."%' OR 
            `key_hotel` LIKE '%".$alterSearch3."%'
        ) ORDER BY id ASC LIMIT 1");
        if($searchHotel->num_rows > 0){
            $searchHotel = $searchHotel->fetch_assoc();
            $toursBase->query("INSERT INTO sufix_hotel_name (`id`, `sufix`, `realName`, `hotel_id`, `country_to`) VALUES (NULL, '".$hotelName."', '".$searchHotel['name']."', '".$searchHotel['id']."', '".$country."');");
            return array( 
                'hotelId'=>$searchHotel['id'],
                'percentage'=>80,
                'info' => $searchHotel
            );
        }else{
            if ($conn->connect_error) {
                die("Ошибка подключения к базе данных: " . $conn->connect_error);
            }
            $hotelName = $conn->real_escape_string($hotelName);
            $country = $conn->real_escape_string($country);
    
            $sql = "SELECT id, name, MATCH(name) AGAINST ('$hotelName' IN NATURAL LANGUAGE MODE) AS score, MATCH(key_hotel) AGAINST ('$hotelName' IN NATURAL LANGUAGE MODE) AS score2
            FROM hotels
            WHERE country_name = '$country'
            ORDER BY score DESC, score2 DESC";
    
            $result = $conn->query($sql);
    
            $bestMatch = '';
            $bestMatchId = '';
            $bestMatchScore = 0;
            $info = null;
    
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $bestMatchId = $row['id'];
                $bestMatch = $row['name'];
                $bestMatchScore = $row['score'];
                $bestMatchScore2 = $row['score2'];
                $info = $row;
            }
    
            if (!empty($bestMatch)) {
                $percentage = round($bestMatchScore, 2);
                $percentage2 = round($bestMatchScore2, 2);
                if($percentage > 16 or $percentage2 > 16){
                    $toursBase->query("INSERT INTO sufix_hotel_name (`id`, `sufix`, `realName`, `hotel_id`, `country_to`) VALUES (NULL, '".$hotelName."', '".$info['name']."', '".$bestMatchId."', '".$country."');");
                    return array(
                        'hotelId'=>$bestMatchId,
                        'percentage'=>round($bestMatchScore, 2),
                        'info' => $info
                    );
                }else{
                    addHotelForRealTimeParse($hotelName, $country);
                    return null;
                }
            } else {
                addHotelForRealTimeParse($hotelName, $country);
                return null;
            }
        }
    }

    function findHotel($hotelName, $country){
        global $toursBase;
        $conn = $toursBase;

        $findeSufixDB = $toursBase->query("SELECT * FROM sufix_hotel_name WHERE country_to='".$country."' AND (sufix='".$hotelName."' OR realName='".$hotelName."') ORDER BY id ASC LIMIT 1");
        if($findeSufixDB->num_rows > 0){
            $findeSufix = $findeSufixDB->fetch_assoc();
            $hotelInfo = $toursBase->query("SELECT * FROM hotels WHERE id='".$findeSufix['hotel_id']."'");
            if($hotelInfo->num_rows > 0){
                $hotelInfo = $hotelInfo->fetch_assoc();
                return array(
                    'hotelId'=>$hotelInfo['id'],
                    'percentage'=>100,
                    'info' => $hotelInfo
                );
            }else{
                return noCashFindeHotel($hotelName, $country);
            }
        }else{
            return noCashFindeHotel($hotelName, $country);
        }
    }

    function searchBaseInfo($nameHotel, $country){
        global $toursBase;
        $findHottel = findHotel($nameHotel, $country);
        if($findHottel != null){
            $hotel =  $findHottel['info'];
            $hotel['images'] = array();
            $imagesHotelsDB = $toursBase->query("SELECT * FROM hotel_images WHERE hotel_id='".$hotel['id']."'");
            while($imagesHotels = $imagesHotelsDB->fetch_assoc()){
                unset($imagesHotels['id']);
                unset($imagesHotels['hotel_id']);
                $hotel['images'][] = $imagesHotels;
            }
            $hotel['region_image'] = array();
            $getRegionImageDB = $toursBase->query("SELECT * FROM regions_image WHERE regions_id='".$hotel['region_id']."' ORDER BY RAND()");
            while($getRegionImage = $getRegionImageDB->fetch_assoc()){
                $hotel['region_image'][] = $getRegionImage['image'];
            }

            $hotel['region_image'] = array_unique($hotel['region_image']);

            $hotel['country_image'] = array();
            $getCountriesImageDB = $toursBase->query("SELECT * FROM countries_image WHERE country_id='".$hotel['country_id']."' ORDER BY RAND()");
            while($getCountriesImage = $getCountriesImageDB->fetch_assoc()){
                $hotel['country_image'][] = $getCountriesImage['image'];
            }

            $hotel['country_image'] = array_unique($hotel['country_image']);

            $hotel['dop_images'] = array();
            $getDopImagesDB = $toursBase->query("SELECT * FROM hotel_dop_image WHERE hotel_id='".$hotel['id']."'");
            while($getDopImages = $getDopImagesDB->fetch_assoc()){
                $hotel['dop_images'][] = $getDopImages['image'];
            }
            $hotel['dop_images'] = array_unique($hotel['dop_images']);


            return array(
                "type"=>"succ",
                "id"=>$findHottel['hotelId'],
                "hotel"=>$hotel['info']
            );
        }else{
            return array(
                "type"=>"err"
            );
        }
    }
    function searchOnlyId($nameHotel, $country){
        global $toursBase;
        $findHottel = findHotel($nameHotel, $country);
        if($findHottel !== null){
            return array(
                "type"=>"succ",
                "id"=>$findHottel['hotelId'],
                "hotel"=> $findHottel['info']
            );
        }else{
            return array(
                "type"=>"err"
            );
        }
    }

    function getHotelInfoMiny($hotelId){ 
        global $toursBase;
        $hotel = $toursBase->query("SELECT * FROM hotels WHERE id='".$hotelId."'");

        if($hotel->num_rows > 0){
            $hotel = $hotel->fetch_assoc();
            $hotelData = array();
            $hotelData['images'] = array();
            $imagesHotelsDB = $toursBase->query("SELECT * FROM hotel_images WHERE hotel_id='".$hotel['id']."' ORDER BY RAND()");
            while($imagesHotels = $imagesHotelsDB->fetch_assoc()){
                unset($imagesHotels['id']);
                unset($imagesHotels['hotel_id']);
                if(empty($imagesHotels['int_image']) == false){
                    $hotelData['images'][] = $imagesHotels['int_image'];
                }else{
                    if(empty($imagesHotels['large_image']) == false){
                        $hotelData['images'][] = $imagesHotels['large_image'];
                    }else{
                        if(empty($imagesHotels['miny_image']) == false){
                            $hotelData['images'][] = $imagesHotels['miny_image'];
                        }
                    }
                }
                
            }

            $getDopImagesDB = $toursBase->query("SELECT * FROM hotel_dop_image WHERE hotel_id='".$hotel['id']."'");
            while($getDopImages = $getDopImagesDB->fetch_assoc()){
                $hotelData['images'][] = $getDopImages['image'];
            }

            $countImage = count($hotelData['images']);
            if($countImage < 12){
                $toursBase->query("UPDATE hotels SET dop_images_load='0', staging_image='0' WHERE id='".$hotelData['id']."' ORDER BY RAND()");
            }

            $getRegionImageDB = $toursBase->query("SELECT * FROM regions_image WHERE regions_id='".$hotel['region_id']."' ORDER BY RAND()");
            while($getRegionImage = $getRegionImageDB->fetch_assoc()){
                $hotelData['images'][] = $getRegionImage['image'];
            }
            $getCountriesImageDB = $toursBase->query("SELECT * FROM countries_image WHERE country_id='".$hotel['country_id']."' ORDER BY RAND()");
            while($getCountriesImage = $getCountriesImageDB->fetch_assoc()){
                $hotelData['images'][] = $getCountriesImage['image'];
            }

            

            $hotelData['id'] = $hotel['id'];
            $hotelData['title'] = $hotel['name'];
            $hotelData['country_title'] = $hotel['country_name'];
            $hotelData['region_title'] = $hotel['city_name'];
            $hotelData['region_id'] = $hotel['region_id'];
            $hotelData['country_id'] = $hotel['country_id'];
            $hotelData['adress'] = $hotel['adress'];
            $hotelData['local_adress'] = $hotel['adressLocalyty'];
            $hotelData['stars'] = $hotel['countStars'];
            $hotelData['lat'] = $hotel['lat'];
            $hotelData['lng'] = $hotel['lng'];
            $hotelData['link_hotel'] = empty($hotel['link_bookong']) == false ? $hotel['link_bookong'] : $hotel['link_trip_advisor'];
            $hotelData['maps_link'] = $hotel['maps_link'];
            $hotelData['reiting'] = $hotel['reiting'];
            $hotelData['count_rew'] = $hotel['summ_rewiew'];
            $hotelData['home_image'] = $hotel['home_image'];

            $hotelData['description-html'] = '';
            $hotelData['description-small'] = '';
            $getDescDB = $toursBase->query("SELECT * FROM hotel_description WHERE hotel_id='".$hotel['id']."' ORDER BY id DESC LIMIT 1");
            if($getDescDB->num_rows > 0){
                $getDesc = $getDescDB->fetch_assoc();
                $hotelData['description-html'] = base64_decode($getDesc['long_desc']);
                $hotelData['description-small'] = base64_decode($getDesc['miny_desc']);
            }

            if(empty($hotelData['dop_images']) == false){
                $hotelData['home_image'] = $hotelData['dop_images'][0];
            }else{
                if($hotelData['home_image'] == 'none' OR empty($hotelData['home_image']) OR $hotelData['home_image']=='loaded'){
                    if(empty($hotelData['images']) == false){
                        $hotelData['home_image'] = $hotelData['images'][0]['int_image'];
                    }else{
                        if(empty($hotelData['region_image']) == false){
                            $hotelData['home_image'] = $hotelData['region_image'][0];
                        }else{
                            if(empty($hotelData['country_image']) == false){
                                $hotelData['home_image'] = $hotelData['country_image'][0];
                            }
                        }
                    }
                }
            }
            return array(
                "type"=>"succ",
                "hotel"=>$hotelData,
            );
        }else{
            return array(
                "type"=>"err",
                "message"=>'Отель не найден, пожалуйста укажите коректный ID отеля!',
            );
        }
    }

    function getHotelInfo($hotelId){ 
        global $toursBase;
        $hotel = $toursBase->query("SELECT * FROM hotels WHERE id='".$hotelId."'");

        if($hotel->num_rows > 0){
            $hotel = $hotel->fetch_assoc();
            $hotelData = array();
            $hotelData['images'] = array();
            $imagesHotelsDB = $toursBase->query("SELECT * FROM hotel_images WHERE hotel_id='".$hotel['id']."' ORDER BY RAND()");
            while($imagesHotels = $imagesHotelsDB->fetch_assoc()){
                unset($imagesHotels['id']);
                unset($imagesHotels['hotel_id']);
                $hotelData['images'][] = $imagesHotels;
            }

            $hotelData['rooms'] = array();
            $roomsHotelDB = $toursBase->query("SELECT * FROM hotels_rooms WHERE hotel_id='".$hotel['id']."'");
            while($roomsHotel = $roomsHotelDB->fetch_assoc()){
                $roomData = array();
                $roomData['title'] = $roomsHotel['title'];
                $roomData['desc'] = $roomsHotel['description'];
                $roomData['max_adult'] = $roomsHotel['max_adult'];
                $roomData['max_children'] = $roomsHotel['max_children'];
                $roomData['max_guest'] = $roomsHotel['max_guest'];
                $roomData['badRooms'] = array();
                $getBadRoomRoomDB = $toursBase->query("SELECT * FROM hotel_rooms_bad_room WHERE room_id='".$roomsHotel['id']."'");
                while($getBadRoomRoom = $getBadRoomRoomDB->fetch_assoc()){
                    unset($getBadRoomRoom['id']);
                    unset($getBadRoomRoom['room_id']);
                    $roomData['badRooms'][] = $getBadRoomRoom;
                }
                $roomData['images'] = array();
                $getRoomsImageDB = $toursBase->query("SELECT * FROM hotel_rooms_images WHERE roo_id='".$roomsHotel['id']."' ORDER BY RAND()");
                while($getRoomsImage = $getRoomsImageDB -> fetch_assoc()){
                    $roomData['images'][] = $getRoomsImage['image'];
                    $hotelData['images'][] = $getRoomsImage['image'];
                }



                
                $roomData['facility'] = array();
                $getFacilityRoomDB = $toursBase->query("SELECT * FROM hotel_rooms_udobstva WHERE room_id='".$roomsHotel['id']."'");
                while($getFacilityRoom = $getFacilityRoomDB->fetch_assoc()){
                    if(empty($getFacilityRoom['group_name'])){
                        $roomData['facility']['Удобства'][] = $getFacilityRoom['name'];
                    }else{
                        if(empty($roomData['facility'][$getFacilityRoom['group_name']])){
                            $roomData['facility'][$getFacilityRoom['group_name']] = array();
                        }
                        $roomData['facility'][$getFacilityRoom['group_name']][] = $getFacilityRoom['name'];
                    }
                }
                $roomData['params'] = array();
                $getRoomParramsDB = $toursBase->query("SELECT * FROM hotel_room_params WHERE room_id='".$roomsHotel['id']."'");
                while($getRoomParrams = $getRoomParramsDB->fetch_assoc()){
                    $roomData['params'][] = $getRoomParrams['name'];
                }

                $hotelData['rooms'][] = $roomData;
            }


            $hotelData['description-html'] = '';
            $hotelData['description-small'] = '';
            $getDescDB = $toursBase->query("SELECT * FROM hotel_description WHERE hotel_id='".$hotel['id']."' ORDER BY id DESC LIMIT 1");
            if($getDescDB->num_rows > 0){
                $getDesc = $getDescDB->fetch_assoc();
                $hotelData['description-html'] = base64_decode($getDesc['long_desc']);
                $hotelData['description-small'] = base64_decode($getDesc['miny_desc']);
            }


            $hotelData['region_image'] = array();
            $getRegionImageDB = $toursBase->query("SELECT * FROM regions_image WHERE regions_id='".$hotel['region_id']."' ORDER BY RAND()");
            while($getRegionImage = $getRegionImageDB->fetch_assoc()){
                $hotelData['region_image'][] = $getRegionImage['image'];
            }
            $hotelData['country_image'] = array();
            $getCountriesImageDB = $toursBase->query("SELECT * FROM countries_image WHERE country_id='".$hotel['country_id']."' ORDER BY RAND()");
            while($getCountriesImage = $getCountriesImageDB->fetch_assoc()){
                $hotelData['country_image'][] = $getCountriesImage['image'];
            }
            $hotelData['dop_images'] = array();
            $getDopImagesDB = $toursBase->query("SELECT * FROM hotel_dop_image WHERE hotel_id='".$hotel['id']."'");
            while($getDopImages = $getDopImagesDB->fetch_assoc()){ 
                $hotelData['dop_images'][] = $getDopImages['image'];
            }


            $countImage = count($hotelData['images']) + count($hotelData['dop_images']);
            if($countImage < 12){
                $toursBase->query("UPDATE hotels SET dop_images_load='0' WHERE id='".$hotel['id']."'");
            }



            $hotelData['reiting'] = array();
            $getHotelReitingDB = $toursBase->query("SELECT * FROM hotel_in_reiting WHERE hotel_id='".$hotel['id']."'");
            while($getHotelReiting = $getHotelReitingDB->fetch_assoc()){
                unset($getHotelReiting['id']);
                unset($getHotelReiting['hotel_id']);
                $hotelData['reiting'][] = $getHotelReiting;
            }
            $hotelData['qwest_answer'] = array();
            $getHotelQwestionDB = $toursBase->query("SELECT * FROM hotel_qwestion WHERE hotel_id='".$hotel['id']."'");
            while($getHotelQwestion = $getHotelQwestionDB->fetch_assoc()){
                unset($getHotelQwestion['id']);
                unset($getHotelQwestion['hotel_id']);
                $hotelData['qwest_answer'][] = $getHotelQwestion;
            }
            $hotelData['rew'] = array();
            $getHotelRewDB = $toursBase->query("SELECT * FROM hotel_rewies WHERE hotel_id='".$hotel['id']."'");
            while($getHotelRew = $getHotelRewDB->fetch_assoc()){
                unset($getHotelRew['id']);
                unset($getHotelRew['hotel_id']);
                $hotelData['rew'][] = $getHotelRew;
            }
            $hotelData['facilitys'] = array();
            $getHotelFacilitysDB = $toursBase->query("SELECT * FROM hotel_udobstva WHERE hotel_id='".$hotel['id']."'");
            while($getHotelFacilitys = $getHotelFacilitysDB->fetch_assoc()){
                if(empty($hotelData['facilitys'][$getHotelFacilitys['group_name']])){
                    $hotelData['facilitys'][$getHotelFacilitys['group_name']] = array();
                }
                $hotelData['facilitys'][$getHotelFacilitys['group_name']][] = $getHotelFacilitys['name'];
            }
            $hotelData['child_policy'] = array();
            $getHotelChildPolicyDB = $toursBase->query("SELECT * FROM hotel_chil_policy WHERE hotel_id='".$hotel['id']."'");
            while($getHotelChildPolicy = $getHotelChildPolicyDB->fetch_assoc()){
                unset($getHotelChildPolicy['id']);
                unset($getHotelChildPolicy['hotel_id']);
                $hotelData['child_policy'][] = $getHotelChildPolicy;
            }
            $hotelData['beaches'] = array();
            $getHotelBeachesDB = $toursBase->query("SELECT * FROM hotel_beaches_info WHERE hotel_id='".$hotel['id']."'");
            while($getHotelBeaches = $getHotelBeachesDB->fetch_assoc()){
                unset($getHotelBeaches['id']);
                unset($getHotelBeaches['hotel_id']);
                $hotelData['beaches'][] = $getHotelBeaches;
            }

            $countryInfo = $toursBase->query("SELECT eng_title FROM countries WHERE id='".$hotel['country_id']."'")->fetch_assoc();

            $hotelData['id'] = $hotel['id'];
            $hotelData['title'] = $hotel['name'];
            $hotelData['country_title'] = $hotel['country_name'];
            $hotelData['region_title'] = $hotel['city_name'];
            $hotelData['region_id'] = $hotel['region_id'];
            $hotelData['country_id'] = $hotel['country_id'];
            $hotelData['eng_country_title'] = $countryInfo['eng_title'];
            $hotelData['adress'] = $hotel['adress'];
            $hotelData['local_adress'] = $hotel['adressLocalyty'];
            $hotelData['stars'] = $hotel['countStars'];
            $hotelData['lat'] = $hotel['lat'];
            $hotelData['lng'] = $hotel['lng'];
            $hotelData['link_hotel'] = empty($hotel['link_bookong']) == false ? $hotel['link_bookong'] : $hotel['link_trip_advisor'];
            $hotelData['maps_link'] = $hotel['maps_link'];
            $hotelData['reiting'] = $hotel['reiting'];
            $hotelData['count_rew'] = $hotel['summ_rewiew'];
            $hotelData['home_image'] = $hotel['home_image'];

            if(empty($hotelData['dop_images']) == false){
                $hotelData['home_image'] = $hotelData['dop_images'][0];
            }else{
                if($hotelData['home_image'] == 'none' OR empty($hotelData['home_image']) OR $hotelData['home_image']=='loaded'){
                    if(empty($hotelData['images']) == false){
                        $hotelData['home_image'] = $hotelData['images'][0]['int_image'];
                    }else{
                        if(empty($hotelData['region_image']) == false){
                            $hotelData['home_image'] = $hotelData['region_image'][0];
                        }else{
                            if(empty($hotelData['country_image']) == false){
                                $hotelData['home_image'] = $hotelData['country_image'][0];
                            }
                        }
                    }
                }
            }
            return array(
                "type"=>"succ",
                "hotel"=>$hotelData,
            );
        }else{
            return array(
                "type"=>"err",
                "message"=>'Отель не найден, пожалуйста укажите коректный ID отеля!',
            );
        }
    }

    function searchHotel($nameHotel, $country){ 
        global $toursBase;
        $hotel = array();
        $findeHottel = findHotel($nameHotel, $country);
        if($findeHottel != null){
            $hotel = getHotelInfo($findeHottel['hotelId']);
            if($hotel['type'] == 'succ'){
                $hotel = $hotel['hotel'];
                return array(
                    'type'=>'succ',
                    'hotel'=>$hotel
                );
            }else{
                return array(
                    'type'=>'err'
                );
            }
        }else{

        }
    }
?>