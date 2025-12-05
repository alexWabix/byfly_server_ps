<?php
    function checkHotelImages($hotelID){
        global $toursBase;

        $countImages = $toursBase->query("SELECT COUNT(*) AS ct FROM hotel_images WHERE hotel_id='".$hotelID."'")->fetch_assoc()['ct'];
        $countImagesDOP = $toursBase->query("SELECT COUNT(*) AS ct FROM hotel_dop_image WHERE hotel_id='".$hotelID."'")->fetch_assoc()['ct'];

        $ctImage = $countImages + $countImagesDOP;
        if($ctImage < 10){
            $toursBase->query("UPDATE hotels SET dop_images_load='0', staging_image='0' WHERE id='".$hotelID."'");
        }

    }
    try {
        if(empty($_POST['url']) == false){
            $searchHotelImagesDB = $toursBase->query("SELECT * FROM hotel_images WHERE int_image='".$_POST['url']."' OR large_image='".$_POST['url']."' OR miny_image='".$_POST['url']."'");
            while($searchHotelImages = $searchHotelImagesDB->fetch_assoc()){
                if($searchHotelImages['int_image'] == $_POST['url']){
                    if(empty($searchHotelImages['large_image']) AND empty($searchHotelImages['miny_image'])){
                        $toursBase->query("DELETE FROM hotel_images WHERE id='".$searchHotelImages['id']."'");
                        checkHotelImages($searchHotelImages['hotel_id']);
                    }else{
                        $toursBase->query("UPDATE hotel_images SET int_image='' WHERE id='".$searchHotelImages['id']."'");
                    }
                }else if($searchHotelImages['large_image'] == $_POST['url']){
                    if(empty($searchHotelImages['int_image']) AND empty($searchHotelImages['miny_image'])){
                        $toursBase->query("DELETE FROM hotel_images WHERE id='".$searchHotelImages['id']."'");
                        checkHotelImages($searchHotelImages['hotel_id']);
                    }else{
                        $toursBase->query("UPDATE hotel_images SET large_image='' WHERE id='".$searchHotelImages['id']."'");
                    }
                }else if($searchHotelImages['miny_image'] == $_POST['url']){
                    if(empty($searchHotelImages['int_image']) AND empty($searchHotelImages['large_image'])){
                        $toursBase->query("DELETE FROM hotel_images WHERE id='".$searchHotelImages['id']."'");
                        checkHotelImages($searchHotelImages['hotel_id']);
                    }else{
                        $toursBase->query("UPDATE hotel_images SET miny_image='' WHERE id='".$searchHotelImages['id']."'");
                    }
                }
            }
    
            $searchDopImagesDB = $toursBase->query("SELECT * FROM hotel_dop_image WHERE image='".$_POST['url']."'");
            while($searchDopImages = $searchDopImagesDB->fetch_assoc()){
                $toursBase->query("DELETE FROM hotel_dop_image WHERE id='".$searchDopImages['id']."'");
                checkHotelImages($searchDopImages['hotel_id']);
            }
    
            $toursBase->query("DELETE FROM countries_image WHERE image='".$_POST['url']."'");
            $toursBase->query("DELETE FROM hotel_rooms_images WHERE image='".$_POST['url']."'");
            $toursBase->query("DELETE FROM regions_image WHERE image='".$_POST['url']."'");
            
            responceApi(true, 'Success generation Responce!', 0, array('data'=>'Remove Succeful!')); 
        }else{
            responceApi(false, "Error generation Responce!", 500);
        }
    } catch (\Throwable $th) {
        responceApi(false, "Error generation Responce!", 500);
    }
    
    
?>