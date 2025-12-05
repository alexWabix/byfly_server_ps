<?php
    try {
        $data = array(
            'counters'=>array(
                'countHotels'=>$toursBase->query("SELECT COUNT(*) as ct FROM hotels")->fetch_assoc()['ct'],
                'countImages'=>0,
                'countSearchTours'=>$toursBase->query("SELECT COUNT(*) as ct FROM search_results_arhive")->fetch_assoc()['ct'],
                'countRew'=>$toursBase->query("SELECT COUNT(*) as ct FROM hotel_rewies")->fetch_assoc()['ct'],
            ),
            'countries'=>array(),
        );

        $countImagesCountry = $toursBase->query("SELECT COUNT(*) as ct FROM countries_image")->fetch_assoc()['ct'];
        $counthotelImages = $toursBase->query("SELECT COUNT(*) as ct FROM hotel_images")->fetch_assoc()['ct'];
        $hotel_dop_image = $toursBase->query("SELECT COUNT(*) as ct FROM hotel_dop_image")->fetch_assoc()['ct'];
        $hotel_rooms_images = $toursBase->query("SELECT COUNT(*) as ct FROM hotel_rooms_images")->fetch_assoc()['ct'];
        $regions_image = $toursBase->query("SELECT COUNT(*) as ct FROM regions_image")->fetch_assoc()['ct'];

        $data['counters']['countImages'] = $countImagesCountry+$counthotelImages+$hotel_dop_image+$hotel_rooms_images+$regions_image;


        $listCountriesDB = $toursBase->query("SELECT id,icon,title,eng_title,sorter,mounth_1,mounth_2,mounth_3,mounth_4,mounth_5,mounth_6,mounth_7,mounth_8,mounth_9,mounth_10,mounth_11,mounth_12 FROM countries ORDER BY sorter DESC");
        while($listCountries = $listCountriesDB->fetch_assoc()){
            $listCountries['images'] = array();
            $listCountries['minPrice'] = 0;
            $listCountries['icon'] = $domain.$listCountries['icon'];
            $imagesGetDB = $toursBase->query("SELECT * FROM countries_image WHERE country_id='".$listCountries['id']."'");
            while($imagesGet = $imagesGetDB->fetch_assoc()){
                $listCountries['images'][] = $imagesGet['image']; 
            }

            if(empty($_POST['myCity'])){
                $getArchiveMinPriceDB = $toursBase->query("SELECT price FROM search_results_arhive WHERE country_to='".$listCountries['title']."' ORDER BY price ASC LIMIT 1");

                $getMinPriceDB = $toursBase->query("SELECT price FROM search_results WHERE country_to='".$listCountries['title']."' ORDER BY price ASC LIMIT 1");
                if($getMinPriceDB->num_rows > 0){
                    $getMinPrice = $getMinPriceDB->fetch_assoc();
                    $listCountries['minPrice'] = $getMinPrice['price'];
                }else{
                    if($getArchiveMinPriceDB->num_rows > 0){
                        $getArchiveMinPrice = $getArchiveMinPriceDB->fetch_assoc();
                        $listCountries['minPrice'] = $getArchiveMinPrice['price'];
                    }
                }
            }else{
                $getArchiveMinPriceDB = $toursBase->query("SELECT price FROM search_results_arhive WHERE country_to='".$listCountries['title']."' AND city_oute='".$_POST['myCity']."' ORDER BY price ASC LIMIT 1");

                $getMinPriceDB = $toursBase->query("SELECT price FROM search_results WHERE country_to='".$listCountries['title']."' AND city_oute='".$_POST['myCity']."' ORDER BY price ASC LIMIT 1");
                if($getMinPriceDB->num_rows > 0){
                    $getMinPrice = $getMinPriceDB->fetch_assoc();
                    $listCountries['minPrice'] = $getMinPrice['price'];
                }else{
                    if($getArchiveMinPriceDB->num_rows > 0){
                        $getArchiveMinPrice = $getArchiveMinPriceDB->fetch_assoc();
                        $listCountries['minPrice'] = $getArchiveMinPrice['price'];
                    }
                }
            }


            $data['countries'][] = $listCountries;
        }


    
        responceApi(true, 'Success generation Responce!', 0, $data);  
    } catch (\Throwable $th) {
        responceApi(false, 'Error Loading: '. $th->getMessage(), 0);  
    }
   
?>