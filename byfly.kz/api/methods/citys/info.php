<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['id']) == false){
        $searchCitys = $toursBase->query("SELECT * FROM regiosns WHERE id='".$_POST['id']."'");
        if($searchCitys->num_rows > 0){
            $resp = $searchCitys->fetch_assoc();
            $resp['images'] = getImagesRegion($resp['id']);
            $resp['desc_text'] = nl2br($resp['desc_text']);
            $resp['countryInfo'] = $toursBase->query("SELECT * FROM countries WHERE id='".$resp['countryid']."'")->fetch_assoc();
            $resp['countryInfo']['icon'] = $domain.$resp['countryInfo']['icon'];

            $resp['popular_hotels'] = array();

            $searchPopularHotels = $toursBase->query("SELECT * FROM hotels WHERE region_id='".$resp['id']."' GROUP BY name ORDER BY reiting DESC LIMIT 10");
            while($hotel = $searchPopularHotels->fetch_assoc()){
                $hotelInfo = getHotelInfoMiny($hotel['id']);
                if($hotelInfo['type'] == 'succ'){
                    $hotelInfo = $hotelInfo['hotel'];
                    $resp['popular_hotels'][] = $hotelInfo;
                }
            }

            $resp['minPrice'] = 0;
            if(empty($_POST['outeCity'])){
                $searchPrice = $toursBase->query("SELECT price FROM search_results WHERE region_to='".$resp['title']."' ORDER BY price ASC LIMIT 1");
                if($searchPrice->num_rows > 0){
                    $resp['minPrice'] = $searchPrice->fetch_assoc()['price'];
                }else{
                    $searchPrice = $toursBase->query("SELECT price FROM search_results_arhive WHERE region_to='".$resp['title']."' ORDER BY price ASC LIMIT 1");
                    if($searchPrice->num_rows > 0){
                        $resp['minPrice'] = $searchPrice->fetch_assoc()['price'];
                    }
                }
            }else{
                $searchPrice = $toursBase->query("SELECT price FROM search_results WHERE region_to='".$resp['title']."' AND city_oute='".$_POST['outeCity']."' ORDER BY price ASC LIMIT 1");
                if($searchPrice->num_rows > 0){
                    $resp['minPrice'] = $searchPrice->fetch_assoc()['price'];
                }else{
                    $searchPrice = $toursBase->query("SELECT price FROM search_results_arhive WHERE region_to='".$resp['title']."' AND city_oute='".$_POST['outeCity']."' ORDER BY price ASC LIMIT 1");
                    if($searchPrice->num_rows > 0){
                        $resp['minPrice'] = $searchPrice->fetch_assoc()['price'];
                    }
                }
            }

           

            responceApi(true, "The city data has been successfully generated.", 0, $resp);
        }else{
            responceApi(false, "The city with the given ID is not registered in the system...", 0);
        }
    }else{
        responceApi(false, "You didn't enter a city ID...", 0);
    }

    function getImagesRegion($id){
        global $toursBase;
        $array = array();
        $listImagesDB = $toursBase->query("SELECT * FROM regions_image WHERE regions_id='".$id."'");
        while($listImages = $listImagesDB->fetch_assoc()){
            $array[] = $listImages['image'];
        }

        return $array;
    }
?>