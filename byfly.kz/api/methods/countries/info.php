<?php
    include('plugins/search_hotel/index.php');
    if(empty($_POST['id']) == false){
        $searchCountry = $toursBase->query("SELECT * FROM countries WHERE id='".$_POST['id']."'");
        if($searchCountry->num_rows > 0){
            $resp = $searchCountry->fetch_assoc();
            $resp['desc_text'] = nl2br($resp['desc_text']);
            $resp['icon'] = $domain.$resp['icon'];
            $resp['images'] = array();
            $searchImage = $toursBase->query("SELECT * FROM countries_image WHERE country_id='".$resp['id']."'");
            while($img = $searchImage->fetch_assoc()){
                $resp['images'][] = $img['image'];
            }
            $resp['minPrice'] = 0;
            $resp['regions'] = array();
            $resp['popular_hotels'] = array();
            

            if(empty($_POST['outeCity'])){
                $searchMinPrice = $toursBase->query("SELECT price FROM search_results WHERE country_to='".$resp['title']."' ORDER BY price ASC LIMIT 1");
                
                if($searchMinPrice->num_rows > 0){
                    $resp['minPrice'] = $searchMinPrice->fetch_assoc()['price'];
                }else{
                    $searchMinPriceArchive = $toursBase->query("SELECT price FROM search_results_arhive WHERE country_to='".$resp['title']."' ORDER BY price ASC LIMIT 1");
                    if($searchMinPriceArchive->num_rows > 0){
                        $resp['minPrice'] = $searchMinPriceArchive->fetch_assoc()['price'];
                    }
                }
            }else{
                $searchMinPrice = $toursBase->query("SELECT price FROM search_results WHERE country_to='".$resp['title']."' AND city_oute='".$_POST['outeCity']."' ORDER BY price ASC LIMIT 1");

                if($searchMinPrice->num_rows > 0){
                    $resp['minPrice'] = $searchMinPrice->fetch_assoc()['price'];
                }else{
                    $searchMinPriceArchive = $toursBase->query("SELECT price FROM search_results_arhive WHERE country_to='".$resp['title']."' AND city_oute='".$_POST['outeCity']."' ORDER BY price ASC LIMIT 1");
                    if($searchMinPriceArchive->num_rows > 0){
                        $resp['minPrice'] = $searchMinPriceArchive->fetch_assoc()['price'];
                    }
                } 
            }

            $searchRegionsDB = $toursBase->query("SELECT id, title, capital FROM regiosns WHERE countryid = '".$resp['id']."' ORDER BY sorter DESC"); 
            while($searchRegions = $searchRegionsDB->fetch_assoc()){
                $searchRegions['images'] = array();
                $getImagesRegionDB = $toursBase->query("SELECT * FROM regions_image WHERE regions_id='".$searchRegions['id']."'");
                while($getImagesRegion = $getImagesRegionDB->fetch_assoc()){
                    $searchRegions['images'][] = $getImagesRegion['image'];
                }
                $resp['regions'][] = $searchRegions;
            }


            $searchPopularHotelsDB = $toursBase->query("SELECT * FROM hotels WHERE country_id='".$resp['id']."' ORDER BY reiting DESC LIMIT 10");
            while($searchPopularHotels = $searchPopularHotelsDB->fetch_assoc()){
                $hotelInfo = getHotelInfoMiny($searchPopularHotels['id']);
                if($hotelInfo['type'] == 'succ'){
                    $hotelInfo = $hotelInfo['hotel'];
                    $resp['popular_hotels'][] = $hotelInfo;
                }
            }

            responceApi(true, "The country data has been successfully generated.", 0, $resp);
        }else{
            responceApi(false, "The country with the given ID is not registered in the system...", 0);
        }
    }else{
        responceApi(false, "You didn't enter a country ID...", 0);
    }
?>