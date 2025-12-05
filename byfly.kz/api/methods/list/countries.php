<?php
    $countries = array();

    $month = date('n');
    $dataType = 'id,icon,title,eng_title';
    if(empty($_POST['extra']) == false and $_POST['extra'] == 1){
        $dataType = '*';
    }
    if(empty($_POST['searchText'])){ 
        if($_POST['isSeason'] == '1'){
            $sql = "SELECT ".$dataType." FROM countries WHERE mounth_".$month."='1' ORDER BY sorter DESC";
        }else{
            $sql = "SELECT ".$dataType." FROM countries ORDER BY sorter DESC";
        }
        
    }else{
        if($_POST['isSeason'] == '1'){
            $sql = "SELECT ".$dataType." FROM countries WHERE title LIKE '%".$_POST['searchText']."%' AND  mounth_".$month."='1' ORDER BY sorter DESC";
        }else{
             $sql = "SELECT ".$dataType." FROM countries WHERE title LIKE '%".$_POST['searchText']."%' ORDER BY sorter DESC";
        }
    }

    if(empty($_POST['limit']) == false){
        $sql .= " LIMIT ".$_POST['limit'];
    }

    $listCountryDB = $toursBase->query($sql);
    
    while($listCountry = $listCountryDB->fetch_assoc()){
        if(empty($_POST['myCitys'])){
            $priceSearch = $toursBase->query("SELECT * FROM search_results_arhive WHERE country_to='".$listCountry['title']."' ORDER BY price ASC LIMIT 1");
            if($priceSearch->num_rows > 0){
                $priceSearch = $priceSearch->fetch_assoc();
                $listCountry['price'] = $priceSearch['price'];
            }else{
                $listCountry['price'] = 0;
            }
        }else{
            $priceSearch = $toursBase->query("SELECT * FROM search_results_arhive WHERE country_to='".$listCountry['title']."' AND city_oute='".$_POST['myCitys']."' ORDER BY price ASC LIMIT 1");
            if($priceSearch->num_rows > 0){
                $priceSearch = $priceSearch->fetch_assoc();
                $listCountry['price'] = $priceSearch['price'];
            }else{
                $listCountry['price'] = 0;
            }
        }
        
        $listCountry['icon'] =  $domain.$listCountry['icon'];
        if(empty($_POST['extra']) == false and $_POST['extra'] == 1){
            $listCountry['images'] = array();
            $listImageDB = $toursBase->query("SELECT * FROM countries_image WHERE country_id ='".$listCountry['id']."'");
            while($listImage = $listImageDB->fetch_assoc()){
                $listCountry['images'][] = $listImage['image'];
            }
        }
        $countries[] = $listCountry;
    }

    responceApi(true, 'Success generation Responce!', 0, $countries); 
?>