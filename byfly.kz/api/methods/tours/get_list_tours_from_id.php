<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['searchID']) == false){
        $searchKey = $toursBase->query("SELECT * FROM search_keys WHERE id='".$_POST['searchID']."'");
        if($searchKey->num_rows > 0){
            $searchKey = $searchKey->fetch_assoc();
            $searchKey['tours'] = array();

            $searhPriceStart = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' ORDER BY price ASC LIMIT 1");
            if($searhPriceStart->num_rows > 0){
                $searchKey['priceStart'] = $searhPriceStart->fetch_assoc()['price'];
            }else{
                $searchKey['priceStart'] = 0;
            }

            $searchPriceEnd = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' ORDER BY price DESC LIMIT 1");
            if($searchPriceEnd -> num_rows > 0){
                $searchKey['priceEnd'] = $searchPriceEnd->fetch_assoc()['price'];
            }else{
                $searchKey['priceEnd'] = 2000000;
            }
            
           

            if(empty($_POST['page'])){
                $_POST['page'] = 1;
            }

            $searchKey['groupedKitchen'] = array();
            $variantKitchenDB = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' GROUP BY pitanie");
            while($variantKitchen = $variantKitchenDB->fetch_assoc()){
                if(empty($variantKitchen['pitanie']) == false){
                    if(!in_array($variantKitchen['pitanie'], $searchKey['groupedKitchen'])) {
                        $searchKey['groupedKitchen'][] = $variantKitchen['pitanie'];
                    }
                }else{
                    $toursBase->query("UPDATE search_results SET pitanie = 'Не указано' WHERE id='".$variantKitchen['id']."'");
                    if(!in_array('Не указано', $searchKey['groupedKitchen'])) {
                        $searchKey['groupedKitchen'][] = 'Не указан';
                    }
                }
            }


            $searchKey['aviaType'] = array();
            $variantAviaDB = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' GROUP BY avia_type");
            while($variantAvia = $variantAviaDB->fetch_assoc()){
                if(empty($variantAvia['avia_type']) == false){
                    if($variantAvia['avia_type'] == 'econom'){
                        $variantAvia['avia_type'] = 'Эконом';
                        $toursBase->query("UPDATE search_results SET avia_type = 'Эконом' WHERE avia_type = 'econom'");
                    }
                    if(!in_array($variantAvia['avia_type'], $searchKey['aviaType'])) {
                        $searchKey['aviaType'][] = $variantAvia['avia_type'];
                    }
                }else{
                    $toursBase->query("UPDATE search_results SET avia_type = 'Эконом' WHERE id='".$variantAvia['id']."'");
                    if(!in_array('Эконом', $searchKey['aviaType'])) {
                        $searchKey['aviaType'][] = 'Эконом';
                    }
                }
            }

            $searchKey['roomType'] = array();
            $variantRoomsDB = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' GROUP BY room_type");
            while($variantRooms = $variantRoomsDB->fetch_assoc()){
                if(empty($variantRooms['room_type']) == false){
                    if(!in_array($variantRooms['room_type'], $searchKey['roomType'])) {
                        $searchKey['roomType'][] = $variantRooms['room_type'];
                    }
                }else{
                    $toursBase->query("UPDATE search_results SET room_type = 'Не указан' WHERE id='".$variantRooms['id']."'");
                    if(!in_array('Не указан', $searchKey['roomType'])) {
                        $searchKey['roomType'][] = 'Не указан';
                    }
                }
            }

            $where = '';
            if(empty($_POST['hotelName']) == false){
                $where .= ' AND hotel_name LIKE "%'.$_POST['hotelName'].'%"';
            }
           
 
            if(empty($_POST['prices']) == false){
                $_POST['prices']= explode(',', $_POST['prices']);
                $where .= ' AND price >= "'.$_POST['prices'][0].'" AND price <= "'.$_POST['prices'][1].'"';
            }
            $startPage = ($_POST['page'] * 12) - 12;

            if(empty($_POST['pitanieType']) == false){
                $_POST['pitanieType'] = json_decode($_POST['pitanieType'], true);
                $pitAnd = '';
                foreach ($_POST['pitanieType'] as $pt) {
                    if($pt['checked'] == true){
                        if(empty($pitAnd)){
                            $pitAnd .= 'pitanie = "'.$pt['val'].'"';
                        }else{
                            $pitAnd .= ' OR pitanie = "'.$pt['val'].'"';
                        }
                    }
                }
                if(empty($pitAnd) == false){
                    $where .= ' AND ('.$pitAnd.')';
                }
            }

           if(empty($_POST['aviaType']) == false){
                $_POST['aviaType'] = json_decode($_POST['aviaType'], true);
                $aviaAnd = '';
                $ct = 0;
                foreach ($_POST['aviaType'] as $av) {
                    if($av['checked'] == true){
                        $ct++;
                        if(empty($aviaAnd)){
                            $aviaAnd .= 'avia_type LIKE "'.$av['val'].'"';
                        }else{
                            $aviaAnd .= ' OR avia_type LIKE "'.$av['val'].'"';
                        }
                    }
                }
                if(empty($aviaAnd) == false){
                    $where .= ' AND ('.$aviaAnd.')';
                }
            }

            

            if(empty($_POST['starts']) == false){
                $_POST['starts'] = json_decode($_POST['starts'], true);
                $starsAnd = '';
                $maxHotelStars = 0;
                foreach ($_POST['starts'] as $st) {
                    if($st['checked'] == true){
                        $maxHotelStars++;
                        if(empty($starsAnd)){
                            $starsAnd .= 'hotel_stars = "'.$st['val'].'"';
                        }else{
                            $starsAnd .= ' OR hotel_stars = "'.$st['val'].'"';
                        }
                    }
                }
                

                if($maxHotelStars < 5){
                    if(empty($starsAnd) == false){
                        $where .= ' AND ('.$starsAnd.')';
                    }
                }
            }


            
            

            $sql = "SELECT *
            FROM search_results AS sr
            WHERE sr.id_search = '".$searchKey['id']."' ".$where."
                AND sr.price = (
                    SELECT MIN(sr2.price)
                    FROM search_results AS sr2
                    WHERE sr2.hotel_name = sr.hotel_name
                        AND sr2.id_search = '".$searchKey['id']."' ".$where."
                )
            GROUP BY sr.hotel_name
            ORDER BY sr.price ASC
            LIMIT ".$startPage.",12";

            
            $sqlCountForPage = "SELECT COUNT(DISTINCT hotel_name) as ct FROM search_results WHERE id_search='".$searchKey['id']."' ".$where;
            $sqlCount = "SELECT COUNT(*) as ct FROM search_results WHERE id_search='".$searchKey['id']."' ".$where;

            $searchKey['real_pages'] = ceil($toursBase->query($sqlCountForPage)->fetch_assoc()['ct'] / 12); 
            $searchKey['real_tours'] = $toursBase->query($sqlCount)->fetch_assoc()['ct'];
            $searchKey['real_count_hotels'] = $toursBase->query($sqlCountForPage)->fetch_assoc()['ct'] ; 

            $searchKey['sql'] = $sqlCount;
            
            $searchTours = $toursBase->query($sql); 
            while($st = $searchTours->fetch_assoc()){
                
                if($st['hotel_id'] > 0){
                    $hotelInfo = getHotelInfo($st['hotel_id']);
                }else{
                    $hotelInfo = searchHotel($st['hotel_name'], $searchKey['countryTo']);
                }
                if($hotelInfo['type'] == 'succ'){
                    $hotelInfo = $hotelInfo['hotel'];
                    
                    $keys = array();
                    $hotelInfo['search_tours'] = array();
                    $toursDB = $toursBase->query("SELECT * FROM search_results WHERE id_search='".$searchKey['id']."' AND  hotel_name='".$hotelInfo['title']."' ORDER BY price ASC");
                    while($tours = $toursDB->fetch_assoc()){
                        if(empty($keys[$tours['id_search']])){
                            $keys[$tours['id_search']] = $toursBase->query("SELECT * FROM search_keys WHERE id='".$tours['id_search']."'")->fetch_assoc();
                            $keys[$tours['id_search']]['childrems'] = explode(',', $keys[$tours['id_search']]['childrems']);
                        }
                        $tours['info_key'] = $keys[$tours['id_search']];
                        $tours['operator_info'] = $toursBase->query("SELECT * FROM sites WHERE id='".$tours['operator_id']."'")->fetch_assoc();  
                        $tours['price'] = $tours['price_shop'];
                        $hotelInfo['search_tours'][] = $tours;
                        
                    }



                    $st['countAdult'] = $searchKey['countAdalt'];
                    $st['child'] = explode(',', str_replace('"', '', $searchKey['childrems']));
                    $childArray = array();
                    foreach ($st['child'] as $child) {
                        if(empty($child) == false){
                            $childArray[] = $child;
                        }
                    }
                    $st['child'] = $childArray;
                    if(count($hotelInfo['search_tours']) > 0){
                        $lastArray = array();
                        $searched = array(); 
                        foreach ($hotelInfo['search_tours'] as $tour) {
                            $tag = md5($tour['avia_date'].$tour['count_nights'].$tour['pitanie'].$tour['price'].$tour['room_type']);
                            if(empty($searched[$tag])){
                                $searched[$tag] = '1';
                                $lastArray[] = $tour;
                            }
                        }  

                        $st['operator_info'] = $toursBase->query("SELECT * FROM sites WHERE id='".$st['operator_id']."'")->fetch_assoc();
                        $st['price'] = $st['price_shop'];

                        $hotelInfo['search_tours'] = $lastArray;
                        //$st['hotel_name'] = $st['hotel_name'].' / '.explode('/', $st['link_for_bron'])[0].explode('/', $st['link_for_bron'])[1].explode('/', $st['link_for_bron'])[2];
                        $searchKey['tours'][] = array(
                            'info'=>$st,
                            'hotel'=>$hotelInfo,
                        );
                    }
                    
                }else{
                    $hotelInfo = null;
                }
            }
            responceApi(true, "List tours generated for page 1.", 0, $searchKey);
        }else{
            responceApi(false, "This ID does not exist or has lost its relevance, please search again.", 0);
        }
    }else{
        responceApi(false, "You didn't provide a tour search ID to display results.", 0);
    }

?>