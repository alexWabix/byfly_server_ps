<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['countryId']) == false){
        $resp = array(
            'countriesInfo' => array(),
            'listHotels' => array(),
            'countAds'=>0,
            'countPages'=>0
        ); 

        $searchCountryDB = $toursBase->query("SELECT id, title, eng_title, icon FROM countries WHERE id='".$_POST['countryId']."'");
        if($searchCountryDB->num_rows > 0){
            $resp['countriesInfo'] = $searchCountryDB->fetch_assoc();
            $resp['countriesInfo']['icon'] = $domain.$resp['countriesInfo']['icon'];
            $resp['countriesInfo']['listRegions'] = array(); 

            $searchRegionDB = $toursBase->query("SELECT id,title,capital FROM regiosns WHERE countryid='".$_POST['countryId']."' ORDER BY sorter DESC");
            while($region = $searchRegionDB->fetch_assoc()){
                $countHotels = $toursBase->query("SELECT COUNT(DISTINCT name) AS ct FROM hotels WHERE region_id='".$region['id']."'")->fetch_assoc()['ct'];
                if($countHotels > 0){
                    $region['count'] = $countHotels;
                    $resp['countriesInfo']['listRegions'][] = $region;
                } 
            }
            $where = '';

            if(empty($_POST['hotelName']) == false){
                $where .= 'AND (name LIKE "%'.$_POST['hotelName'].'%" OR key_hotel LIKE "'.$_POST['hotelName'].'") ';
            }

            if(empty($_POST['stars']) == false){
                $_POST['stars'] = json_decode($_POST['stars'], true);
                $sql = '';
                $countChecked = 0;
                foreach ($_POST['stars'] as $stars) {
                    if($stars['checked']){
                        if(empty($sql)){
                            $sql .= "countStars = '".$stars['count']."' ";
                        }else{
                            $sql .= "OR countStars = '".$stars['count']."' ";
                        }
                        $countChecked++;
                    }
                }

                if($countChecked !== 5 AND $countChecked !== 0){
                    $where .= ' AND ('.$sql.') ';
                }
            }

            if(empty($_POST['regionId']) OR $_POST['regionId'] == null OR $_POST['regionId'] == 'null'){
                $sql = "SELECT * FROM hotels WHERE country_id ='".$_POST['countryId']."' ".$where." GROUP BY name ORDER BY countStars DESC, reiting DESC LIMIT ";
                $sqlCount = "SELECT COUNT(DISTINCT name) AS ct FROM hotels WHERE country_id ='".$_POST['countryId']."' ".$where;
                $resp['regionInfo'] = false;
            }else{
                $sql = "SELECT * FROM hotels WHERE country_id ='".$_POST['countryId']."' AND region_id='".$_POST['regionId']."' ".$where." GROUP BY name ORDER BY countStars DESC, reiting DESC LIMIT ";
                $sqlCount = "SELECT COUNT(DISTINCT name) AS ct FROM hotels WHERE country_id ='".$_POST['countryId']."' AND region_id='".$_POST['regionId']."' ".$where;

                $resp['regionInfo'] = $toursBase->query("SELECT id,title FROM regiosns WHERE id='".$_POST['regionId']."'")->fetch_assoc();
            } 

            if(empty($_POST['page'])){
                $_POST['page'] = 1;
            }

            $startPage = ($_POST['page'] * 12) - 12;

            $sql .= $startPage.', 12';  

            $count = $toursBase->query($sqlCount)->fetch_assoc()['ct'];
            $resp['countAds'] = $count;  
            $resp['countPages'] = ceil($count/12);
            $resp['test'] = isset($_POST['regionId']);

            $hotelsDB = $toursBase->query($sql);
            while($hotels = $hotelsDB->fetch_assoc()){
                $searchHotel = getHotelInfoMiny($hotels['id']);
                if($searchHotel['type'] == 'succ'){
                    $resp['listHotels'][] = $searchHotel['hotel'];
                }
            }
            

            responceApi(true, 'Success generation Responce!', 0, $resp); 
        }else{
            responceApi(false, "Country with specified ID does not exist", 0); 
        }
    }else{
        responceApi(false, "You didn't enter a country ID", 0); 
    }

    
?>