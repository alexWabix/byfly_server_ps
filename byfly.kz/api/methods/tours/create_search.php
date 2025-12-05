<?php
    set_time_limit(0);
    ignore_user_abort(true);
    use duzun\hQuery;
    include_once $dir.'cron/vendor/autoload.php';
    include($dir.'config/methods/getPages.php');
    
    ini_set('max_execution_time',0);
    ini_set('memory_limit', '2048M');



    require_once($dir.'config/lybtrary/angry-curl/RollingCurl.class.php');
    require_once($dir.'config/lybtrary/angry-curl/AngryCurl.class.php');

    if(empty($_POST['childrems'])){ 
        $chld = '';
    }else{
        $chld = json_encode($_POST['childrems']);
    }

    if(empty($_POST['regionTo'])){
        $_POST['regionTo'] = 'Все';
    }

    if(empty($_POST['page'])){
        $_POST['page'] = 1;
    }
    $_POST['hotelStars'] = explode(',', $_POST['hotelStars']);
    $checkCountNight = explode(' - ', $_POST['defoultSelectedNight']);
    $countryTo = $_POST['countryTo'];
    $cityTo = $_POST['regionTo']; 
    $countryOute = $_POST['countryOute'];
    $cityOute = $_POST['cityOute']; 

    $systems = $toursBase->query("SELECT * FROM systems WHERE id = '1'")->fetch_assoc();
    $percentageSys = $systems['percentage']; 

    
    
    if(count($_POST['hotelStars']) > 0 and count($_POST['hotelStars']) < 3){
        if($checkCountNight[0] > 3){
            if($checkCountNight[0] < $checkCountNight[1]){
                $diapozonNight = $checkCountNight[1] - $checkCountNight[0];
                
                if($diapozonNight < 14){
                    $_POST['date2'] = explode(' - ', $_POST['date']);
                    $_POST['date2'][0] = explode('/', $_POST['date2'][0]);
                    $_POST['date2'][1] = explode('/', $_POST['date2'][1]);
 

                    $_POST['date2'][0] = date('Y-m-d H:i:s', strtotime($_POST['date2'][0][2].'-'.$_POST['date2'][0][1].'-'.$_POST['date2'][0][0]));
                    $_POST['date2'][1] = date('Y-m-d H:i:s', strtotime($_POST['date2'][1][2].'-'.$_POST['date2'][1][1].'-'.$_POST['date2'][1][0]));

                    $raznica = $dateDiff = date_diff(new DateTime($_POST['date2'][0]), new DateTime($_POST['date2'][1]))->days;

                    if($raznica <= 30){
                        if($_POST['countAdalt'] > 0 and $_POST['countAdalt'] < 10){
                            if(empty($_POST['countryOute']) == false){
                                if(empty($_POST['cityOute']) == false){
                                    if(empty($_POST['countryTo'])){
                                        responceApi($type = false, $message = 'Destination country not specified.', 0);
                                        exit();
                                    }
                                }else{
                                    responceApi($type = false, $message = 'Departure city not specified.', 0);
                                    exit();
                                }
                            }else{
                                responceApi($type = false, $message = 'Departure country not specified.', 0);
                                exit();
                            }
                        }else{
                            responceApi($type = false, $message = 'The number of people should not exceed 10 people and be at least 1.', 0);
                            exit();
                        }
                    }else{
                        responceApi($type = false, $message = 'The date range must not exceed 30 days.', 0);
                        exit();
                    }
                }else{
                    responceApi($type = false, $message = 'Number of nights not filled correctly. Minimum number of nights 3, maximum 14.', 0);
                    exit();
                }
            }else{
                responceApi($type = false, $message = 'The range of nights for searching for tours is not correctly specified.', 0);
                exit();
            }
        }else{
            responceApi($type = false, $message = 'The minimum number of nights when searching for tours is 3.', 0);
            exit();
        }
    }else{
        responceApi($type = false, $message = 'Incorrect number of stars for the hotel', 0);
        exit();
    }
    

    
    $rrHotelStars = json_encode($_POST['hotelStars'], JSON_UNESCAPED_UNICODE);
    $rrNights = json_encode($_POST['defoultSelectedNight'], JSON_UNESCAPED_UNICODE);
    $date = json_encode($_POST['date'], JSON_UNESCAPED_UNICODE);

    $countSearchLinksCallBack = 0;
    $countSearchErrLinks= 0;
    $coundLinkParce = 0;

    $_POST['defoultSelectedNight'] =  $checkCountNight;
    if(empty($_POST['gds'])){
        $_POST['gds'] = 1;
    }

    $_POST['gds'] = 1; 

    $qSearch = "SELECT * FROM search_keys WHERE countryOute='".$_POST['countryOute']."' AND cityOute='".$_POST['cityOute']."' AND countryTo='".$_POST['countryTo']."' AND regionTo='".$_POST['regionTo']."' AND gds='".$_POST['gds']."' AND hotelStars='".$rrHotelStars."' AND defoultSelectedNight='".$rrNights."' AND countAdalt='".$_POST['countAdalt']."' AND childrems='".$chld."' AND date='".$date."' AND count_tours > '0' ORDER BY id DESC LIMIT 1";
    $searchResults = $toursBase->query($qSearch);

    if($searchResults->num_rows == 0){
        ob_start();
        $toursBase->query("INSERT INTO search_keys (`id`, `count_pages`, `count_tours`, `count_search_links`, `count_err_links`, `date_create`, `countryOute`, `cityOute`, `countryTo`, `regionTo`, `gds`, `hotelStars`, `defoultSelectedNight`, `countAdalt`, `childrems`, `date`, `loading`, `load_links`, `time_load`) VALUES (NULL, '0', '0', '0', '0', CURRENT_TIMESTAMP, '".$_POST['countryOute']."', '".$_POST['cityOute']."', '".$_POST['countryTo']."', '".$_POST['regionTo']."', '".$_POST['gds']."', '".$rrHotelStars."', '".$rrNights."', '".$_POST['countAdalt']."', '".$chld."', '".$date."', '0', '0', '0');");
        $start = microtime(true);
         
        $idSearchKey = $toursBase->insert_id;

        echo json_encode(array(
            "succ"=>true,
            "data"=>array(
                "search_id"=>$idSearchKey,
                "count_tours"=>0,
                "count_pages"=>0,
                "loading_progress"=>0,
            )
        ), JSON_UNESCAPED_UNICODE);

        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        
        ob_end_flush();
        ob_flush();
        flush();



        $AC = new AngryCurl('callback_function');

        $gds = '';

        if(empty($_POST['gds'])){
            $_POST['gds'] = 0;
        }

        if($_POST['gds'] == 0){
            $gds = " AND gds='0'";
        }else if($_POST['gds'] == 1){
            $gds = "";
        }else{
            $gds = " AND gds='1'";
        }

        $arrLinksResp = array();
        $ctLinked = 0;
        

        $_POST['date'] = explode(' - ', $_POST['date']);
        $_POST['date'][0] = explode('/', $_POST['date'][0]);
        $_POST['date'][1] = explode('/', $_POST['date'][1]);

        $dateStartForTez = date("d.m.Y", strtotime($_POST['date'][0][2].'-'.$_POST['date'][0][1].'-'.$_POST['date'][0][0]));
        $dateOffForTez = date("d.m.Y", strtotime($_POST['date'][1][2].'-'.$_POST['date'][1][1].'-'.$_POST['date'][1][0]));

        $dateStartForPegas = date("Y-m-d", strtotime($_POST['date'][0][2].'-'.$_POST['date'][0][1].'-'.$_POST['date'][0][0]));
        $dateOffForPegas = date("Y-m-d", strtotime($_POST['date'][1][2].'-'.$_POST['date'][1][1].'-'.$_POST['date'][1][0]));

        $dateStartForSanat = date("Y-m-d", strtotime($_POST['date'][0][2].'-'.$_POST['date'][0][1].'-'.$_POST['date'][0][0]));
        $dateOffForSanat = date("Y-m-d", strtotime($_POST['date'][1][2].'-'.$_POST['date'][1][1].'-'.$_POST['date'][1][0]));

        $_POST['date'][0] = date("Ymd", strtotime($_POST['date'][0][2].'-'.$_POST['date'][0][1].'-'.$_POST['date'][0][0]));
        $_POST['date'][1] = date("Ymd", strtotime($_POST['date'][1][2].'-'.$_POST['date'][1][1].'-'.$_POST['date'][1][0]));

        $samoIdOperators = '';
        $getOperatorsSamo = $toursBase->query("SELECT id FROM sites WHERE type = 'samo'");
        while($samo = $getOperatorsSamo->fetch_assoc()){
            if(empty($samoIdOperators)){
                $samoIdOperators .= 'operator_id = "'.$samo['id'].'" ';
            }else{
                $samoIdOperators .= 'OR operator_id = "'.$samo['id'].'" ';
            }
        }



        if($_POST['regionTo'] == 'Все'){
            $queryText = "SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND (".$samoIdOperators.") GROUP BY tour_params_id";
            $listLink = $toursBase->query($queryText);
            while($ls = $listLink->fetch_assoc()){ 
                $ctLinked++;
                $operatorInfo = $toursBase->query("SELECT id, name, type,logo, link, 1starsid, 2starsid, 3starsid, 4starsid, 5starsid,curencyid FROM sites WHERE id = '".$ls['operator_id']."'");
                $ls['operator_info'] = $operatorInfo->fetch_assoc();
                $link = $ls['operator_info']['link'].'?samo_action=PRICES&TOWNFROMINC='.$ls['city_oute_id'].'&STATEINC='.$ls['country_to_id'].'&TOURINC='.$ls['tour_params_id'];
                    $searchStars = array();
                    $starsAll = false;
                    if(empty($_POST['hotelStars'][1])){
                        if(empty($ls['operator_info'][$_POST['hotelStars'][0].'starsid']) == false){
                            array_push($searchStars, $ls['operator_info'][$_POST['hotelStars'][0].'starsid']);
                        }
                    }else{
                        $starsAll = true;
                    }

                    if($starsAll){
                        $link = $link.'&STARS=&STARS_ANY=1';
                    }else{
                        $link = $link.'&STARS='.implode(',',$searchStars)."&STARS_ANY=0";
                    }

                    
                    
                    $link = $link.'&NIGHTS_FROM='.$_POST['defoultSelectedNight'][0].'&NIGHTS_TILL='.$_POST['defoultSelectedNight'][1];
                    if(empty($_POST['childrems'])){
                        $link = $link.'&ADULT='.$_POST['countAdalt'].'&CHILD=';
                    }else{
                        $link = $link.'&ADULT='.$_POST['countAdalt'].'&CHILD='.implode(',', $_POST['childrems']);
                    }
                    
                    $link = $link.'&HOTELS_ANY=&HOTELS=';
                    $link = $link.'&FREIGHT=1&FILTER=1&MOMENT_CONFIRM=0&UFILTER=&HOTELTYPES=&PARTITION_PRICE=0&PRICEPAGE=1&TOWNS_ANY=1&TOWNS=&CURRENCY='.$ls['operator_info']['curencyid'];
                    $link = $link.'&CHECKIN_END='.$_POST['date'][1].'&CHECKIN_BEG='.$_POST['date'][0];
                    
                    array_push($arrLinksResp, array('operator'=> $ls['operator_info'], "link"=>$link));
            }
        }else{
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' AND country_to='".$_POST['countryTo']."' AND region_to='".$_POST['regionTo']."' ".$gds." AND (".$samoIdOperators.") GROUP BY operator_id");
            while($ls = $listLink->fetch_assoc()){
                $ls['arrCitys'] = array();
                $listLinkCity = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' AND country_to='".$_POST['countryTo']."' AND region_to='".$_POST['regionTo']."' AND operator_id='".$ls['operator_id']."' ".$gds." GROUP BY city_to_id");
                $operatorInfo = $toursBase->query("SELECT id, name, type, logo, link, 1starsid, 2starsid, 3starsid, 4starsid, 5starsid,curencyid FROM sites WHERE id = '".$ls['operator_id']."'");
                $ls['operator_info'] = $operatorInfo->fetch_assoc();
                $paramsArray = array();
                while($ls2 = $listLinkCity->fetch_assoc()){
                    array_push($ls['arrCitys'], $ls2['city_to_id']);
                }
                $paramsTo = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' AND country_to='".$_POST['countryTo']."' AND region_to='".$_POST['regionTo']."' AND operator_id='".$ls['operator_id']."' ".$gds." GROUP BY tour_params_id");
                while($pr = $paramsTo->fetch_assoc()){
                    array_push($paramsArray, $pr['tour_params_id']);
                }

                foreach ($paramsArray as $pr) {
                    $link = $ls['operator_info']['link'].'?samo_action=PRICES&TOWNFROMINC='.$ls['city_oute_id'].'&STATEINC='.$ls['country_to_id'].'&TOURINC='.$pr.'&TOWNS='.implode(',', array_unique($ls['arrCitys']));
                    $searchStars = array();
                    $starsAll = false;
                    if(empty($_POST['hotelStars'][1])){
                        if(empty($ls['operator_info'][$_POST['hotelStars'][0].'starsid']) == false){
                            array_push($searchStars, $ls['operator_info'][$_POST['hotelStars'][0].'starsid']);
                        }
                    }else{
                        $starsAll = true;
                    }

                    if($starsAll){
                        $link = $link.'&STARS=&STARS_ANY=1';
                    }else{
                        $link = $link.'&STARS='.implode(',',$searchStars)."&STARS_ANY=0";
                    }

                    
                    $link = $link.'&NIGHTS_FROM='.$_POST['defoultSelectedNight'][0].'&NIGHTS_TILL='.$_POST['defoultSelectedNight'][1];
                    if(empty($_POST['childrems'])){
                        $link = $link.'&ADULT='.$_POST['countAdalt'].'&CHILD=';
                    }else{
                        $link = $link.'&ADULT='.$_POST['countAdalt'].'&CHILD='.implode(',', $_POST['childrems']);
                    }
                        
                    $link = $link.'&HOTELS_ANY=&HOTELS=';
                    $link = $link.'&FREIGHT=1&FILTER=1&MOMENT_CONFIRM=0&UFILTER=&HOTELTYPES=&PARTITION_PRICE=1&PRICEPAGE=0&TOWNS_ANY=0&CURRENCY='.$ls['operator_info']['curencyid'];
                    $link = $link.'&CHECKIN_END='.$_POST['date'][1].'&CHECKIN_BEG='.$_POST['date'][0];

                    $ctLinked++;
                    array_push($arrLinksResp, array('operator'=> $ls['operator_info'], "link"=>$link));
                }
            }
        }
        


        $tezLinks = array();
        $summLinksTez = 0;
        $getOperatorTez = $toursBase->query("SELECT * FROM sites WHERE type = 'tez'")->fetch_assoc();
        if($_POST['regionTo'] == 'Все'){
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorTez['id']."' GROUP BY city_to_id");
        }else{
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorTez['id']."' AND region_to='".$_POST['regionTo']."' GROUP BY city_to_id");
        }

        $paramsAdalt = array(
            "acc" => 1,
            "umn" => 0
        );
        if($_POST['countAdalt'] == 1){
            $paramsAdalt = array(
                "acc" => 1,
                "umn" => 0
            );
        }else if($_POST['countAdalt']  == 2){
            $paramsAdalt = array(
                "acc" => 2,
                "umn" => 0
            );
        }else if($_POST['countAdalt']  == 3){
            $paramsAdalt = array(
                "acc" => 3,
                "umn" => 0
            );
        }else if($_POST['countAdalt']  == 4){
            $paramsAdalt = array(
                "acc" => 2,
                "umn" => 2
            );
        }else if($_POST['countAdalt']  > 4){
            $paramsAdalt = array(
                "acc" => 1,
                "umn" => $_POST['countAdalt']
            );
        }

        while($ll = $listLink->fetch_assoc()){
            if(empty($_POST['hotelStars'][1])){
                $i=0;
                $summLinksTez++;
                $url = 'https://search.tez-tour.com/tariffsearch/getResult?
                &tourId='.$ll['city_to_id'].'
                &countryId='.$ll['country_to_id'].'
                &cityId='.$ll['city_oute_id'].'
                &priceMin=0
                &priceMax=999999
                &currency='.$getOperatorTez['curencyid'].'
                &nightsMin='.$_POST['defoultSelectedNight'][0].'
                &nightsMax='.$_POST['defoultSelectedNight'][1].'
                &hotelClassId='.$getOperatorTez[$_POST['hotelStars'][0].'starsid'].'
                &accommodationId='.$paramsAdalt['acc'].'
                &formatResult=false
                &tourType=1
                &groupByHotel=2
                &locale=ru
                &after='.$dateStartForTez.'
                &before='.$dateOffForTez.'
                &hotelClassBetter=true
                &rAndBBetter=true
                &hotelInStop=false
                &specialInStop=false
                &noTicketsTo=false
                &noTicketsFrom=false
                &promoFlag=true
                &version=2
                &searchTypeId=6
                &birthdays=';
                $summLinksTez++;
                array_push($tezLinks, array('operator'=> $getOperatorTez, "link"=>preg_replace('/\s\s+/', '', $url), "umn"=>$paramsAdalt['umn']));
            }else{
                $i=0;
                $url = 'https://search.tez-tour.com/tariffsearch/getResult?
                &tourId='.$ll['city_to_id'].'
                &countryId='.$ll['country_to_id'].'
                &cityId='.$ll['city_oute_id'].'
                &priceMin=0
                &priceMax=999999
                &currency='.$getOperatorTez['curencyid'].'
                &nightsMin='.$_POST['defoultSelectedNight'][0].'
                &nightsMax='.$_POST['defoultSelectedNight'][1].'
                &hotelClassId='.$getOperatorTez['2starsid'].'
                &accommodationId=2
                &formatResult=false
                &tourType=1
                &groupByHotel=2
                &locale=ru
                &after='.$dateStartForTez.'
                &before='.$dateOffForTez.'
                &hotelClassBetter=true
                &rAndBBetter=true
                &hotelInStop=false
                &specialInStop=false
                &noTicketsTo=false
                &noTicketsFrom=false
                &promoFlag=true
                &version=2
                &searchTypeId=6
                &birthdays=';
                $summLinksTez++;
                array_push($tezLinks, array('operator'=> $getOperatorTez, "link"=>preg_replace('/\s\s+/', '', $url), "umn"=>$paramsAdalt['umn']));
            }
        }


        
        $sanatLinks = array();
        $summLinksSanat = 0;
        $getOperatorSanat = $toursBase->query("SELECT * FROM sites WHERE type = 'sanat'")->fetch_assoc();
        if($_POST['regionTo'] == 'Все'){
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorSanat['id']."' GROUP BY country_to");
        }else{
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorSanat['id']."' AND region_to='".$_POST['regionTo']."' GROUP BY region_to");
        }

    


        $sanatDuration = '';
        $_POST['defoultSelectedNight'][0] = $_POST['defoultSelectedNight'][0] - 1;
        while($_POST['defoultSelectedNight'][0]++<$_POST['defoultSelectedNight'][1]){
            $sanatDuration .= '&Durations='.$_POST['defoultSelectedNight'][0];
        }


        $stars ='';
        if(empty($_POST['hotelStars'][1])){
            if(empty($getOperatorSanat[$_POST['hotelStars'][0].'starsid']) == false){
                $stars .='&HotelStars='.$getOperatorSanat[$_POST['hotelStars'][0].'starsid'];
            }
        }else{
            $i=0;
            while($i++<$_POST['hotelStars'][1]){
                if(empty($getOperatorSanat[$i.'starsid']) == false){
                    $stars .='&HotelStars='.$getOperatorSanat[$i.'starsid'];
                }
            }
        }

        $child = '';
        if(empty($_POST['childrems']) == false){
            foreach ($_POST['childrems'] as $chAge) {
                $child .= '&ChildAges='.$chAge;
            }
        }else{
            $child = '&ChildAges=';
        }


        while($ll = $listLink->fetch_assoc()){
            $dateForSan = '';
            $dr = $dateStartForSanat;
            while($dr<$dateOffForSanat){
                $nextDate = date('d.m.Y',strtotime($dr . "+1 days"));
                $dr = date('Y-m-d',strtotime($dr . "+1 days"));
                $dateForSan .= '&Dates='.$nextDate;
            }
            $ll['tour_params_id'] = '';

            if($_POST['regionTo'] == 'Все'){
                $listParams = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorSanat['id']."' GROUP BY tour_params_id");
            }else{
                $listParams = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorSanat['id']."' AND region_to='".$_POST['regionTo']."' GROUP BY tour_params_id");
            }

            while($lp = $listParams->fetch_assoc()){
                $ll['tour_params_id'] .= '&TourType='.$lp['tour_params_id'];
            }


            $url = 'http://194.39.66.27:9000/TourSearchOwin/Tour
            ?DepartureCityKeys='.$ll['city_oute_id'].'
            '.$dateForSan.'
            '.$sanatDuration.'
            &PageNumber=1
            &PageSize=30
            &HotelScheme=
            &TourKey=
            &TourDuration=
            &ShowToursWithoutHotels=-1
            &isFromBasket=false
            &isFillSecondaryFilters=false
            &DestinationType=1
            &DestinationKey='.$ll['country_to_id'].'
            &AdultCount='.$_POST['countAdalt'].'
            &CurrencyName=Tg
            &AviaQuota=5
            '.$child.'
            &HotelQuota=5
            &BusTransferQuota=7
            &RailwayTransferQuota=5
            '.$ll['tour_params_id'].'
            &TimeDepartureFrom=00%3A00
            &TimeDepartureTo=23%3A59
            &TimeArrivalFrom=00%3A00
            &TimeArrivalTo=23%3A59
            &SearchId=5
            '.$stars.'
            &wrongLicenseFileUpperTitle=Некорректный+файл+лицензии.
            &RemoteHotelMode=0
            &_=1663348874238';

            
            $summLinksSanat++;
            array_push($sanatLinks, array('operator'=> $getOperatorSanat, "link"=>preg_replace('/\s\s+/', '', $url)));
        }


        $pegasLinks = array();
        $summLinksPegas = 0;
        $getOperatorPegas = $toursBase->query("SELECT * FROM sites WHERE type = 'pegas'")->fetch_assoc();
        if($_POST['regionTo'] == 'Все'){
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorPegas['id']."' GROUP BY tour_params_id");
        }else{
            $listLink = $toursBase->query("SELECT * FROM tour_params WHERE country_oute ='".$_POST['countryOute']."' AND city_oute='".$_POST['cityOute']."' AND country_to='".$_POST['countryTo']."' ".$gds." AND operator_id='".$getOperatorPegas['id']."' AND region_to='".$_POST['regionTo']."' GROUP BY tour_params_id");
        }


        $stars = array();
        if(empty($_POST['hotelStars'][1])){
            if(empty($getOperatorPegas[$_POST['hotelStars'][0].'starsid']) == false){
                array_push($stars, (int)$getOperatorPegas[$_POST['hotelStars'][0].'starsid']);
            }
        }else{
            $i=0;
            while($i++<$_POST['hotelStars'][1]){
                if(empty($getOperatorPegas[$i.'starsid']) == false){
                    array_push($stars, (int)$getOperatorPegas[$i.'starsid']);
                }
            }
        }


        $PersonAges = array();
        $i=0;
        while($i++<$_POST['countAdalt']){
            array_push($PersonAges, 35);
        }


        if(empty($_POST['childrems']) == false){
            foreach ($_POST['childrems'] as $chAge) {
                array_push($PersonAges, (int)$chAge);
            }
        }


        $dur_nights2 = array();
        $dur_nights = array_filter(explode('&Durations=', $sanatDuration), function($element) {
            return !empty($element);
        });

        foreach ($dur_nights as $key) {
            array_push($dur_nights2, (int)$key);
        }


        while($llc = $listLink->fetch_assoc()){
            if($_POST['regionTo'] == 'Все'){
                $llc['city_to_id'] = null;
            }else{
                $llc['city_to_id'] = (int)$llc['city_to_id'];
            }
            $dateForPeg = array();
            $dd = $dateStartForPegas;
            $countDate = 0;
            while($dd<$dateOffForPegas){
                if($countDate == 0){
                    $nextDateY = date('Y',strtotime($dd));
                    $nextDateM = date('m',strtotime($dd));
                    $nextDated = date('d',strtotime($dd));
        
                    $nextDateM = $nextDateM - 1;
                    $dd = date('Y-m-d',strtotime($dd));
                    if($countDate < 30){
                        array_push($dateForPeg, array("Day"=>(int)$nextDated, "Month"=>(int)$nextDateM, "Year"=>(int)$nextDateY));
                    }
                }else{
                    $nextDateY = date('Y',strtotime($dd . "+1 days"));
                    $nextDateM = date('m',strtotime($dd . "+1 days"));
                    $nextDated = date('d',strtotime($dd . "+1 days"));

                    $nextDateM = $nextDateM - 1;
                    $dd = date('Y-m-d',strtotime($dd . "+1 days"));
                    if($countDate < 30){
                        array_push($dateForPeg, array("Day"=>(int)$nextDated, "Month"=>(int)$nextDateM, "Year"=>(int)$nextDateY));
                    }
                }
                
                $countDate++;
            }
            $arrayPost = array(
                "AirlineIds"=>array(),
                "BasicFares"=>null,
                "DepartureLocationId"=>(int)$llc['city_oute_id'],
                "DestinationLocationId"=>$llc['city_to_id'],
                "DurationsInNights"=>$dur_nights2,
                "FlightsWithAvailableSeats"=>true,
                "FlightsWithSeatsOnRequest"=>true,
                "FlightsWithoutStops"=>false,
                "GroupByHotel"=>true,
                "InstantConfirmationOnly"=>false,
                "LanguageCode"=>"RU",
                "MainHotelAttributeIds"=>array(),
                "MainHotelCategoryGroupIds"=>$stars,
                "MainHotelCountryIds"=>array((int)$llc['country_to_id']),
                "MainHotelIds"=>array(),
                "MainHotelLocationAreaIds"=>array(),
                "MainHotelLocationIds"=>array(),
                "MainHotelRegionIds"=>array(),
                "MainHotelMealGroupIds"=>array(
                    6890,
                    16700,
                    16699,
                    16698,
                    2115431
                ),
                "MaxPaymentCurrencyPrice"=>null,
                "MinPaymentCurrencyPrice"=>null,
                "OutgoingFlightClassId"=>null,
                "PackageIds"=>array((int)$llc['tour_params_id']),
                "PackageSpoTypeIds"=>array(),
                "PaymentCurrencyId"=>(int)$getOperatorPegas['curencyid'],
                "PersonAges"=>$PersonAges,
                "RenderAlternativeReturnLocations"=>true,
                "ReturnFlightClassId"=>null,
                "ReturnLocationId"=>(int)$llc['city_oute_id'],
                "StartDates"=>$dateForPeg,
                "WithoutStopSales"=>true
            );

            $summLinksPegas++;
            array_push($pegasLinks, array("operator"=>$getOperatorPegas, "link"=>$getOperatorPegas['link'].'/PackageCalculation/DisplaySearchResult', "post"=>$arrayPost));
        }

        $allLinks = array();
        foreach ($arrLinksResp as $key) {
            if(empty($allLinks['operator-'.$key['operator']['id']]['link'])){
                $allLinks['operator-'.$key['operator']['id']]['link'] = array();
                $allLinks['operator-'.$key['operator']['id']]['info'] = $key['operator'];
            }
            array_push($allLinks['operator-'.$key['operator']['id']]['link'], $key['link']);
        }


        foreach ($tezLinks as $key) {
            if(empty($allLinks['operator-'.$key['operator']['id']]['link'])){
                $allLinks['operator-'.$key['operator']['id']]['link'] = array();
                $allLinks['operator-'.$key['operator']['id']]['info'] = $key['operator'];
            }
            array_push($allLinks['operator-'.$key['operator']['id']]['link'], $key['link']);
        }

        foreach ($sanatLinks as $key) {
            if(empty($allLinks['operator-'.$key['operator']['id']]['link'])){
                $allLinks['operator-'.$key['operator']['id']]['link'] = array();
                $allLinks['operator-'.$key['operator']['id']]['info'] = $key['operator'];
            }
            array_push($allLinks['operator-'.$key['operator']['id']]['link'], $key['link']);
        }


        foreach ($pegasLinks as $key) {
            if(empty($allLinks['operator-'.$key['operator']['id']]['link'])){
                $allLinks['operator-'.$key['operator']['id']]['link'] = array();
                $allLinks['operator-'.$key['operator']['id']]['info'] = $key['operator'];
            }
            array_push($allLinks['operator-'.$key['operator']['id']]['link'], array('link'=>$key['link'], "post"=>$key['post']));
        }

        $dataLinked = array();
        $countAddLinkedThread = 0;
        $countProxys = $toursBase->query("SELECT COUNT(*) as ct FROM proxy WHERE status='1' GROUP BY operator_id")->fetch_assoc()['ct'];
        foreach ($allLinks as $key => $links) {
            $countLinksForOperator = 0;
            foreach ($links['link'] as $lk) {
                if($links['info']['id'] == 7){
                    $lk = $lk.'&COMFORTABLE_SEATS=0';
                    $lk = str_replace('PARTITION_PRICE=0','PARTITION_PRICE=224', $lk);
                }
                if($links['info']['type'] == 'pegas'){
                    if($lk['post'] = array_map('boolsToString', $lk['post'])){
                        $getProxyForURL = $toursBase->query("SELECT * FROM proxy WHERE operator_id = '".$links['info']['id']."' AND status = '1' ORDER BY summ_get ASC LIMIT 1");
                        if($getProxyForURL->num_rows > 0){
                            $getProxyForURL = $getProxyForURL->fetch_assoc();
                            $getProxyForURL['summ_get'] = $getProxyForURL['summ_get'] + 1;
    
                            $toursBase->query("UPDATE proxy SET summ_get = '".$getProxyForURL['summ_get']."' WHERE id='".$getProxyForURL['id']."'");
                            $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31';
                            $coundLinkParce++;
                            $AC->post(
                                $lk['link'], 
                                http_build_query($lk['post']), 
                                $headers = null, 
                                $options = array(
                                    CURLOPT_SSL_VERIFYPEER => false,
                                    CURLOPT_VERBOSE => false,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_PROXY => $getProxyForURL['url'],
                                    CURLOPT_PROXYUSERPWD => $getProxyForURL['auth'],
                                    CURLOPT_FAILONERROR => true,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_REFERER => "google.com",
                                    CURLOPT_USERAGENT => $agent
                                )
                            );
                            
                            $countAddLinkedThread++;
                        } 
                    }
                    
                }else{ 
                    $getProxyForURL = $toursBase->query("SELECT * FROM proxy WHERE operator_id = '".$links['info']['id']."' AND status = '1' ORDER BY summ_get ASC LIMIT 1");
                    if($getProxyForURL->num_rows > 0){
                        $getProxyForURL = $getProxyForURL->fetch_assoc();
                        $getProxyForURL['summ_get'] = $getProxyForURL['summ_get'] + 1;
                        $toursBase->query("UPDATE proxy SET summ_get = '".$getProxyForURL['summ_get']."' WHERE id='".$getProxyForURL['id']."'");
                        $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31';
                        $coundLinkParce++;
                        $AC->get($lk, $headers = null, $options = array(
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_VERBOSE => false,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_PROXY => $getProxyForURL['url'],
                            CURLOPT_PROXYUSERPWD => $getProxyForURL['auth'],
                            CURLOPT_FAILONERROR => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_REFERER => "google.com",
                            CURLOPT_USERAGENT => $agent
                        ));

                        $countAddLinkedThread++;
                    } 
                }
            }
        }

        $toursBase->query("UPDATE search_keys SET load_links='".$coundLinkParce."' WHERE id='".$idSearchKey."'");

        if($countAddLinkedThread > 0){
            $AC->execute(15);
        }
        
        unset($AC); 
        $time = microtime(true) - $start;
        $toursBase->query("UPDATE search_keys SET loading='100', time_load='".$time."' WHERE id='".$idSearchKey."'");

        $summLinkEatch = $countSearchLinksCallBack + $countSearchErrLinks;

        $searchCountry = $toursBase->query("SELECT * FROM countries WHERE title LIKE '".$countryTo."' ORDER BY id DESC LIMIT 1");
        if($searchCountry->num_rows > 0){
            $searchCountry = $searchCountry->fetch_assoc();
            $searchCountry['sorter'] = $searchCountry['sorter'] + 1;
            $toursBase->query("UPDATE countries SET sorter='".$searchCountry['sorter']."' WHERE id='".$searchCountry['id']."'");
        }
    }else{
        $searchResults = $searchResults->fetch_assoc();
        echo json_encode(array(
            "succ"=>true,
            "data"=>array(
                "search_id"=>$searchResults['id'],
            )
        ), JSON_UNESCAPED_UNICODE);

        $searchCountry = $toursBase->query("SELECT * FROM countries WHERE title LIKE '".$countryTo."' ORDER BY id DESC LIMIT 1");
        if($searchCountry->num_rows > 0){
            $searchCountry = $searchCountry->fetch_assoc();
            $searchCountry['sorter'] = $searchCountry['sorter'] + 1;
            $toursBase->query("UPDATE countries SET sorter='".$searchCountry['sorter']."' WHERE id='".$searchCountry['id']."'");
        }
    }


    function boolsToString ($value) {
        if ($value === true) return 'true';   
        if ($value === false) return 'false';
        return $value;
    }

    function getSanatTours($page){
        global $toursBase;
        $data = json_decode($page, true);
        if(empty($data['Result']) == false){
            if(count($data['Result']) > 0){
                $arrayTours = array();
                foreach ($data['Result'] as $toursInfo) {
                    $add = true;
                    if($_POST['gds'] == 0){
                        if(stripos($toursInfo['Name'], 'GDS') === false and stripos($toursInfo['Name'], 'gds') === false and stripos($toursInfo['Name'], 'regular') === false){
                            $add = false;
                        }
                    }
                    $hotelInfo = array();

                    foreach ($toursInfo['Services'] as $value) {
                        if(empty($value['Hotel']) == false){
                            $hotelInfo = $value;
                        }
                    }

                    if($add){
                        $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$hotelInfo['HotelDetails'][0]['Pansion']['Value']."'");
                        if($searchPitanie->num_rows > 0){
                            $searchPitanie = $searchPitanie->fetch_assoc();
                            if(empty($searchPitanie['new_key']) == false){
                                $hotelInfo['HotelDetails'][0]['Pansion']['Value'] = $searchPitanie['new_key'];
                            }
                        }else{
                            $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$hotelInfo['HotelDetails'][0]['Pansion']['Value']."', '', '0');");
                        }


                        array_push($arrayTours, array(
                            'date'=>date('d-m-Y', strtotime($toursInfo['StartDate'])), 
                            'parametrsName'=>$toursInfo['Name'],
                            "countNight"=>$toursInfo['Duration'], 
                            "hotel"=>$hotelInfo['Hotel']['Value'].' '.$hotelInfo['Stars']['Value'],
                            "hotel-link"=>$hotelInfo['Http'],
                            "imageHotel"=>$hotelInfo['ImageURL'],
                            "pitanie"=>$hotelInfo['HotelDetails'][0]['Pansion']['Value'],
                            "roomType"=>$hotelInfo['HotelDetails'][0]['RoomCategory']['Value'],
                            "price"=>$toursInfo['Cost'],   
                            "priceOld"=>'',   
                            "bronLink"=>'',
                            "typePrice"=>'',
                            "transport"=>$toursInfo['FlightClass'],
                            "data-townfrom"=>'',
                            "data-state"=>'',
                            "data-checkin"=>'',
                            "data-nights"=>'',
                            "data-hnights"=>'',
                            "data-cat-claim"=>'',
                            "data-packet-type"=>'',
                            "data-hotel"=>'',
                            "data-statefrom"=>''
                        ));
                    }
                }

                if(count($arrayTours) > 0){
                    $page = array(
                        "type" => true,
                        "tours"=>$arrayTours,
                    );
                }else{
                    $page = array(
                        "type"=>false,
                        "mess"=>'Туры не найдены!'
                    );
                }
            }else{
                $page = array(
                    "type"=>true,
                    "mess"=>'Переменная Result пустая!'
                );
            }
        }else{
            $page = array(
                "type"=>true,
                "mess"=>'Переменная Result пустая!'
            );
        }
        return $page;
    }

    function getHotelStars($hotel_name) {
        $stars = null;
        
        if (preg_match('/(\d+)\s?\*/', $hotel_name, $matches)) {
          $stars = $matches[1];
        }
        
        return $stars;
    }

    function searchHotelSufix($hotelName, $countryTo){
        global $toursBase;
        $searchSufix = $toursBase->query("SELECT * FROM sufix_hotel_name WHERE sufix LIKE '".$hotelName."' AND country_to LIKE '".$countryTo."' ORDER BY id DESC LIMIT 1");
        if($searchSufix->num_rows > 0){
            $searchSufix = $searchSufix->fetch_assoc();
            $hotelInfo = $toursBase->query("SELECT * FROM hotels WHERE id='".$searchSufix['hotel_id']."'");
            if($hotelInfo->num_rows > 0){
                return array(
                    "search"=>true,
                    "name"=>$searchSufix['realName'],
                    "id"=>$searchSufix['hotel_id'],
                    "hotel_info"=>$hotelInfo->fetch_assoc()
                );
            }else{
                return array(
                    "search"=>false,
                    "name"=>$hotelName,
                    "id"=>0
                );  
            }
                
        }else{
            return array(
                "search"=>false,
                "name"=>$hotelName,
                "id"=>0
            );
        }
    }


    function determineCategory($text) {
        $lowercaseText = strtolower($text);
        
        if (strpos($lowercaseText, 'эконом') !== false || strpos($lowercaseText, 'econom') !== false) {
            return 'Эконом';
        } elseif (strpos($lowercaseText, 'бизнес') !== false || strpos($lowercaseText, 'business') !== false) {
            if (strpos($lowercaseText, 'эконом') === false && strpos($lowercaseText, 'econom') === false) {
                return 'Бизнес';
            }
        }
        
        return 'Эконом';
    }


    function insertSearchResult(
        $idSearchKey, 
        $parametrsName, 
        $countNight, 
        $hotel_link, 
        $pitanie, 
        $price, 
        $priceOld, 
        $bronLink, 
        $typePrice, 
        $transport, 
        $roomType, 
        $operator, 
        $hotel, 
        $date
    ){
        global $toursBase;
        global $countryTo;
        global $cityTo;
        global $countryOute;
        global $cityOute;
        global $percentageSys;

        

        $realCitys = $cityTo;
        $hotel = str_replace("`", "", str_replace("'", "", str_replace('"', '', $hotel)));
        $roomType = str_replace("`", "", str_replace("'", "", str_replace('"', '', $roomType)));
        $stars = getHotelStars($hotel);
        $srSuf = searchHotelSufix($hotel, $countryTo);
        $hotel = $srSuf['name'];
        $hotel_id = $srSuf['id'];
        if($srSuf['search']){ 
            $checked = 1;
            $realCitys = $srSuf['hotel_info']['city_name'];
        }else{
            $checked = 0;
        }
        if(empty($transport)){
            $transport = 'Эконом';
        }else{
            $transport = determineCategory($transport);
        }

        if(empty($roomType)){
            $roomType = 'Не указан';
        }

        if(empty($pitanie)){
            $pitanie = 'Не указано';
        }
        $parametrsName = str_replace("`", "", str_replace("'", "", str_replace('"', '', str_replace(',', ':', $parametrsName))));

        if(empty($priceOld)){
            $priceOld = 0;
        }

        
        $priceShop = ceil($price - (($price / 100) * $percentageSys)); // Убираем накрутку
        $sql = "INSERT INTO search_results (
            `id`, 
            `id_search`, 
            `name_package`, 
            `count_nights`, 
            `hotel_link`, 
            `pitanie`, 
            `price`,
            `price_shop`,
            `old_price`, 
            `link_for_bron`, 
            `type_price`, 
            `avia_type`, 
            `operator_id`, 
            `room_type`, 
            `hotel_name`, 
            `avia_date`, 
            `chacked`, 
            `home_image`, 
            `hotel_id`, 
            `hotel_stars`, 
            `country_to`,
            `region_to`,
            `country_oute`,
            `city_oute`
            ) VALUES (
                NULL, 
                '".$idSearchKey."', 
                '".$parametrsName."', 
                '".$countNight."', 
                '".$hotel_link."', 
                '".$pitanie."', 
                '".$price."', 
                '".$priceShop."',
                '".$priceOld."', 
                '".$bronLink."', 
                '".$typePrice."', 
                '".$transport."', 
                '".$operator."', 
                '".$roomType."', 
                '".$hotel."',
                '".$date."', 
                '".$checked."', 
                '', 
                '".$hotel_id."', 
                '".$stars."', 
                '".$countryTo."', 
                '".$realCitys."',
                '".$countryOute."',
                '".$cityOute."'
            );";
        if($toursBase->query($sql)){
            return array(
                'succ'=>true,
            );
        }else{ 
            return array(
                'succ'=>false,
                'mess'=> 'Не удалось записать тур в базу данных! - '.str_replace("`", "", str_replace("'", "", str_replace('"', '', $toursBase->error))), 
            );  
        }
        
    }


    function callback_function($response, $info, $request){
        global $AC;
        global $idSearchKey;
        global $count_tours;
        global $toursBase;
        global $countSearchLinksCallBack;
        global $countSearchErrLinks;
        global $coundLinkParce;
        global $summAddLinks;

        

        $realLink = $info['url'];
        $webSite = parse_url($realLink)['host'];
        $proxy =  $request->options[CURLOPT_PROXY];
        $checkStart = true;
        $searchOperator = $toursBase->query("SELECT * FROM sites WHERE link LIKE '%".$webSite."%' ORDER BY id DESC LIMIT 1");
        if($searchOperator->num_rows > 0){
            $operator = $searchOperator->fetch_assoc(); 
            $checkStart = true;
        }else{
            $checkStart = false;
        }
        if($checkStart){
            $searchProxy = $toursBase->query("SELECT * FROM proxy WHERE url='".$proxy."' AND operator_id='".$operator['id']."' ORDER BY id DESC LIMIT 1");
            if($searchProxy ->num_rows > 0){
                $proxyInfo = $searchProxy->fetch_assoc(); 
                $checkStart = true;
            }else{
                $checkStart = false;
            }
        }

        
        
        if(!$checkStart){
            $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, 'Не удалось распознать оператора или прокси: ".$realLink." - ".$webSite." - ".$proxy."', CURRENT_TIMESTAMP, '".$operator['name']."', '0', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
            return;
        }


        try {
            if($info['http_code']!==200 ){
                $countSearchErrLinks++;
                $proxyInfo['countERR'] = $proxyInfo['countERR'] + 1;
                $toursBase->query("UPDATE proxy SET  countERR='".$proxyInfo['countERR']."', lasterr='Ошибка подключения: ".$info['http_code']." |||| ".$operator['name']." |||| ".json_encode($info)."', last_send='".date('Y-m-d H:i:s')."' WHERE id='".$proxyInfo['id']."'");
                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, 'Ошибка запроса туров: ".json_encode($info)." ||| ".$response."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
            }else{
                if(strpos($response, 'каптча') !== false or strpos($response, 'Captcha') !== false){
                    $countSearchErrLinks++;
                    $proxyInfo['countERR'] = $proxyInfo['countERR'] + 1;
                    $toursBase->query("UPDATE proxy SET status='0', countERR='".$proxyInfo['countERR']."', lasterr='Каптча', last_send='".date('Y-m-d H:i:s')."' WHERE id='".$proxyInfo['id']."'");
                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, 'Каптча', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                }else{
                    $countSearchLinksCallBack++;
                    $toursBase->query("UPDATE proxy SET status='1', last_send='".date('Y-m-d H:i:s')."' WHERE id='".$proxyInfo['id']."'");
                    
                    if($operator['type'] == 'tez'){
                        $resp = json_decode($response, true); 
                        if($resp['success'] == false){
                            if(mb_stristr($resp['message'], 'Интервал количества ночей не может быть больше', 'utf-8') !== false){
                                $countedNigtsMax = preg_replace("/[^0-9]/", '', $resp['message']);
                                $urlParser = parse_url($realLink);
                                parse_str($urlParser['query'], $arrayParams);
    
                                $thisNightsMin = $arrayParams['nightsMin'];
                                $thisNightsMax = $arrayParams['nightsMax'];
    
                                $linked = array();
                                $counted = ceil($thisNightsMax / $countedNigtsMax);
                                $i = 0;
                                while($i++ < $counted){
                                    $toLeft = $i - 1;
                                    $newMin = $thisNightsMin + ($toLeft * $countedNigtsMax);
                                    $newMax = $newMin + $countedNigtsMax; 
                                    if($newMax > $thisNightsMax){
                                        $newMax = $thisNightsMax;
                                    }
                                    
    
                                    $appendParams = $arrayParams;
                                    $appendParams['nightsMin'] = $newMin;
                                    $appendParams['nightsMax'] = $newMax;


                                   
                                    $getProxyForURL = $toursBase->query("SELECT * FROM proxy WHERE operator_id = '".$operator['id']."' AND status = '1' ORDER BY summ_get ASC LIMIT 1");
                                    if($getProxyForURL->num_rows > 0){
                                        $getProxyForURL = $getProxyForURL->fetch_assoc();
                                        $getProxyForURL['summ_get'] = $getProxyForURL['summ_get'] + 1;
                                        $toursBase->query("UPDATE proxy SET summ_get = '".$getProxyForURL['summ_get']."' WHERE id='".$getProxyForURL['id']."'");
                                        $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31'; 
                                        $coundLinkParce++;
                                        $toursBase->query("UPDATE search_keys SET load_links='".$coundLinkParce."' WHERE id='".$idSearchKey."'");
                                        $AC->get('https://search.tez-tour.com/tariffsearch/getResult?'.http_build_query($appendParams), $headers = null, $options = array(
                                            CURLOPT_SSL_VERIFYPEER => false,
                                            CURLOPT_VERBOSE => false,
                                            CURLOPT_RETURNTRANSFER => true,
                                            CURLOPT_PROXY => $getProxyForURL['url'],
                                            CURLOPT_PROXYUSERPWD => $getProxyForURL['auth'],
                                            CURLOPT_FAILONERROR => true,
                                            CURLOPT_FOLLOWLOCATION => true,
                                            CURLOPT_REFERER => "google.com",
                                            CURLOPT_USERAGENT => $agent
                                        )); 
                                    }
                                } 
                            }else{
                                $getTours = getTezTours($response);
                                if($getTours['type']){
                                    if(!empty($getTours['tours'])){
                                        foreach($getTours['tours'] as $tour){
                                            $insert = insertSearchResult(
                                                $idSearchKey, 
                                                $tour['parametrsName'], 
                                                $tour['countNight'], 
                                                $tour['hotel-link'], 
                                                $tour['pitanie'], 
                                                $tour['price'], 
                                                $tour['priceOld'], 
                                                $tour['bronLink'], 
                                                $tour['typePrice'], 
                                                $tour['transport'], 
                                                $tour['roomType'], 
                                                $operator['id'], 
                                                $tour['hotel'], 
                                                $tour['date']
                                            );
                                            
    
                                            if(!$insert['succ']){
                                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                            }else{
                                                $count_tours++;
                                            }
                                        }
                                    }
                                }else{
                                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                }
                            }
                        }else{
                            $getTours = getTezTours($response);
                            if($getTours['type']){
                                if(!empty($getTours['tours'])){
                                    foreach($getTours['tours'] as $tour){
                                        $insert = insertSearchResult(
                                            $idSearchKey, 
                                            $tour['parametrsName'], 
                                            $tour['countNight'], 
                                            $tour['hotel-link'], 
                                            $tour['pitanie'], 
                                            $tour['price'], 
                                            $tour['priceOld'], 
                                            $tour['bronLink'], 
                                            $tour['typePrice'], 
                                            $tour['transport'], 
                                            $tour['roomType'], 
                                            $operator['id'], 
                                            $tour['hotel'], 
                                            $tour['date']
                                        );
    
                                        if(!$insert['succ']){
                                            $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                        }else{
                                            $count_tours++;
                                        }
                                    }
                                }
                            }else{
                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                            }
                        }
                    }else if($operator['type'] == 'samo'){
                        $page = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                            }, $response);
                        $page = htmlspecialchars_decode(stripcslashes(htmlentities($page)));
                        
                        if(mb_stristr($page, 'Уменьшите интервал дат до', 'utf-8') !== false){
                            $positionText = strpos($page, 'Уменьшите интервал дат до');
                            $countNightsDown = preg_replace("/[^0-9]/", '', mb_substr($page, $positionText, 27));
    
                            parse_str(parse_url($realLink)['query'], $urlParse);
    
                            $dateStart = date("Y-m-d H:i:s", strtotime(mb_substr($urlParse['CHECKIN_BEG'], 0, 4).'-'.mb_substr($urlParse['CHECKIN_BEG'], 4, 2).'-'.mb_substr($urlParse['CHECKIN_BEG'], 6, 2)));
                            $dateEnd = date("Y-m-d H:i:s", strtotime(mb_substr($urlParse['CHECKIN_END'], 0, 4).'-'.mb_substr($urlParse['CHECKIN_END'], 4, 2).'-'.mb_substr($urlParse['CHECKIN_END'], 6, 2)));
                            $daysRaznica = date_diff(new DateTime($dateEnd), new DateTime($dateStart))->days;
                            $countLinks = ceil($daysRaznica / $countNightsDown);
                            $summAddLinks = 0;
                            $i=0;
                            while($i++<$countLinks){
                                $leftI = $i-1;
                                $startAdd = $leftI * $countNightsDown;
                                $modifyDateStart = new DateTime($dateStart);
                                $modifyDateStart->modify('+'.$startAdd.' days');
    
    
                                $modifyDateEnd = new DateTime($modifyDateStart->format('Y-m-d H:i:s'));
                                $modifyDateEnd->modify('+'.$countNightsDown.' days');
    
                                $paramsUrl = $urlParse;
                                $paramsUrl['CHECKIN_END'] = $modifyDateEnd->format('Ymd');
                                $paramsUrl['CHECKIN_BEG'] = $modifyDateStart->format('Ymd'); 
    
                                $getProxyForURL = $toursBase->query("SELECT * FROM proxy WHERE operator_id = '".$operator['id']."' AND status = '1' ORDER BY summ_get ASC LIMIT 1");
                                if($getProxyForURL->num_rows > 0){
                                    $getProxyForURL = $getProxyForURL->fetch_assoc();
                                    $getProxyForURL['summ_get'] = $getProxyForURL['summ_get'] + 1;
                                    $toursBase->query("UPDATE proxy SET summ_get = '".$getProxyForURL['summ_get']."' WHERE id='".$getProxyForURL['id']."'");
                                    $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31';
                                    $summAddLinks++;
                                    $coundLinkParce++;
                                    $toursBase->query("UPDATE search_keys SET load_links='".$coundLinkParce."' WHERE id='".$idSearchKey."'");
                                    $AC->get($operator['link'].'/?'.http_build_query($paramsUrl), $headers = null, $options = array(
                                        CURLOPT_SSL_VERIFYPEER => false,
                                        CURLOPT_VERBOSE => false,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_PROXY => $getProxyForURL['url'],
                                        CURLOPT_PROXYUSERPWD => $getProxyForURL['auth'],
                                        CURLOPT_FAILONERROR => true,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_REFERER => "google.com",
                                        CURLOPT_USERAGENT => $agent
                                    ));
                                }
    
                            }
                        }else if(mb_stristr($page, 'Уменьшите интервал ночей до', 'utf-8') !== false){
                            $positionText = strpos($page, 'Уменьшите интервал ночей до');
                            $countNightsDown = preg_replace("/[^0-9]/", '', mb_substr($page, $positionText, 29));
                            parse_str(parse_url($realLink)['query'], $urlParse);
    
                            $countLinks = ceil($urlParse['NIGHTS_TILL'] / $countNightsDown);
                            $i = 0;
                            while($i++ < $countLinks){
                                $toLeft = $i-1;
                                $urlParams = $urlParse;
    
                                $urlParams['NIGHTS_FROM'] = $urlParams['NIGHTS_FROM'] + ($toLeft * $countNightsDown);
                                $urlParams['NIGHTS_TILL'] = $urlParams['NIGHTS_FROM'] + $countNightsDown;
                                if($urlParams['NIGHTS_TILL'] > $urlParse['NIGHTS_TILL']){
                                    $urlParams['NIGHTS_TILL'] = $urlParse['NIGHTS_TILL'];
                                }
    
                                $getProxyForURL = $toursBase->query("SELECT * FROM proxy WHERE operator_id = '".$operator['id']."' AND status = '1' ORDER BY summ_get ASC LIMIT 1");
                                if($getProxyForURL->num_rows > 0){
                                    $getProxyForURL = $getProxyForURL->fetch_assoc();
                                    $getProxyForURL['summ_get'] = $getProxyForURL['summ_get'] + 1;
                                    $toursBase->query("UPDATE proxy SET summ_get = '".$getProxyForURL['summ_get']."' WHERE id='".$getProxyForURL['id']."'");
                                    $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31';
                                    $summAddLinks++;
                                    $coundLinkParce++;
                                    $toursBase->query("UPDATE search_keys SET load_links='".$coundLinkParce."' WHERE id='".$idSearchKey."'");
                                    $AC->get($operator['link'].'/?'.http_build_query($urlParams), $headers = null, $options = array(
                                        CURLOPT_SSL_VERIFYPEER => false,
                                        CURLOPT_VERBOSE => false,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_PROXY => $getProxyForURL['url'],
                                        CURLOPT_PROXYUSERPWD => $getProxyForURL['auth'],
                                        CURLOPT_FAILONERROR => true,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_REFERER => "google.com",
                                        CURLOPT_USERAGENT => $agent
                                    ));
                                }
    
                            }
                        }else{
                            if($operator['id'] == 7){
                                $getTours = getToursSamoAnex($response);
                                if($getTours['type']){
                                    if(!empty($getTours['tours'])){
                                        foreach($getTours['tours'] as $tour){
                                            $tour['bronLink'] = $operator['bron_link']. $tour['data-cat-claim'];
                                            $insert = insertSearchResult(
                                                $idSearchKey, 
                                                $tour['parametrsName'], 
                                                $tour['countNight'], 
                                                $tour['hotel-link'], 
                                                $tour['pitanie'], 
                                                $tour['price'], 
                                                $tour['priceOld'], 
                                                $tour['bronLink'], 
                                                $tour['typePrice'], 
                                                $tour['transport'], 
                                                $tour['roomType'], 
                                                $operator['id'], 
                                                $tour['hotel'], 
                                                $tour['date']
                                            );
    
                                            if(!$insert['succ']){
                                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                            }else{
                                                $count_tours++;
                                            }
                                        }
                                    }
                                }else{
                                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']." - ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                }
                            }else if($operator['id'] == 8){
                                $getTours = getToursSamoJoin($response);
                                if($getTours['type']){
                                    if(!empty($getTours['tours'])){
                                        foreach($getTours['tours'] as $tour){
                                            $tour['bronLink'] = $operator['bron_link']. $tour['data-cat-claim'];
                                            $insert = insertSearchResult(
                                                $idSearchKey, 
                                                $tour['parametrsName'], 
                                                $tour['countNight'], 
                                                $tour['hotel-link'], 
                                                $tour['pitanie'], 
                                                $tour['price'], 
                                                $tour['priceOld'], 
                                                $tour['bronLink'], 
                                                $tour['typePrice'], 
                                                $tour['transport'], 
                                                $tour['roomType'], 
                                                $operator['id'], 
                                                $tour['hotel'], 
                                                $tour['date']
                                            );
    
                                            if(!$insert['succ']){
                                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                            }else{
                                                $count_tours++;
                                            }
                                        }
                                    }
                                }else{
                                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']." - ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                }
                            }else if($operator['id'] == 1){
                                $getTours = getToursSamoFunSun($response);

                                if($getTours['type']){
                                    if(!empty($getTours['tours'])){
                                        foreach($getTours['tours'] as $tour){
                                            $tour['bronLink'] = $operator['bron_link']. $tour['data-cat-claim'];
                                            $insert = insertSearchResult(
                                                $idSearchKey, 
                                                $tour['parametrsName'], 
                                                $tour['countNight'], 
                                                $tour['hotel-link'], 
                                                $tour['pitanie'], 
                                                $tour['price'], 
                                                $tour['priceOld'], 
                                                $tour['bronLink'], 
                                                $tour['typePrice'], 
                                                $tour['transport'], 
                                                $tour['roomType'], 
                                                $operator['id'], 
                                                $tour['hotel'], 
                                                $tour['date']
                                            );

                                            if(!$insert['succ']){
                                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                            }else{
                                                $count_tours++;
                                            }
                                        }
                                    }
                                }else{
                                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                }
                            }else{
                                $getTours = getToursSamo($response);

                                if($getTours['type']){
                                    if(!empty($getTours['tours'])){
                                        foreach($getTours['tours'] as $tour){
                                            $tour['bronLink'] = $operator['bron_link']. $tour['data-cat-claim'];
                                            $insert = insertSearchResult(
                                                $idSearchKey, 
                                                $tour['parametrsName'], 
                                                $tour['countNight'], 
                                                $tour['hotel-link'], 
                                                $tour['pitanie'], 
                                                $tour['price'], 
                                                $tour['priceOld'], 
                                                $tour['bronLink'], 
                                                $tour['typePrice'], 
                                                $tour['transport'], 
                                                $tour['roomType'], 
                                                $operator['id'], 
                                                $tour['hotel'], 
                                                $tour['date']
                                            );

                                            if(!$insert['succ']){
                                                $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                            }else{
                                                $count_tours++;
                                            }
                                        }
                                    }
                                }else{
                                    $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                }
                            }
                        }
                    }else if($operator['type'] == 'pegas'){
                        $getTours = getPegasTours($response);
                        if($getTours['type']){
                            if(!empty($getTours['tours'])){
                                foreach($getTours['tours'] as $tour){
                                    $insert = insertSearchResult(
                                        $idSearchKey, 
                                        $tour['parametrsName'], 
                                        $tour['countNight'], 
                                        $tour['hotel-link'], 
                                        $tour['pitanie'], 
                                        $tour['price'], 
                                        $tour['priceOld'], 
                                        $tour['bronLink'], 
                                        $tour['typePrice'], 
                                        $tour['transport'], 
                                        $tour['roomType'], 
                                        $operator['id'], 
                                        $tour['hotel'], 
                                        $tour['date']
                                    );
    
                                    if(!$insert['succ']){
                                        $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                    }else{
                                        $count_tours++;
                                    }
                                }
                            }
                        }else{
                            $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                        }
                    }else if($operator['type'] == 'sanat'){
                        $getTours = getSanatTours($response);
                        if($getTours['type']){
                            if(!empty($getTours['tours'])){
                                foreach($getTours['tours'] as $tour){
                                    $insert = insertSearchResult(
                                        $idSearchKey, 
                                        $tour['parametrsName'], 
                                        $tour['countNight'], 
                                        $tour['hotel-link'], 
                                        $tour['pitanie'], 
                                        $tour['price'], 
                                        $tour['priceOld'], 
                                        $tour['bronLink'], 
                                        $tour['typePrice'], 
                                        $tour['transport'], 
                                        $tour['roomType'], 
                                        $operator['id'], 
                                        $tour['hotel'], 
                                        $tour['date']
                                    );
    
                                    if(!$insert['succ']){
                                        $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$insert['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                                    }else{
                                        $count_tours++;
                                    }
                                }
                            }
                        }else{
                            $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, '".$getTours['mess']."- ".$realLink."', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
                        }
                    }

                    $toursKeyInfo = $toursBase->query("SELECT * FROM search_keys WHERE id='".$idSearchKey."'")->fetch_assoc();
                    $obrLinks = $toursKeyInfo['count_search_links'] + $toursKeyInfo['count_err_links'];
                    $percentage = ceil(($obrLinks * 100) / $toursKeyInfo['load_links']);
    
                    $toursBase->query("UPDATE search_keys SET count_pages='".ceil($count_tours/12)."', count_tours='".$count_tours."', count_search_links='".$countSearchLinksCallBack."', count_err_links='".$countSearchErrLinks."', loading='".$percentage."' WHERE id='".$idSearchKey."'");
                }
            }
            return;
        } catch (\Throwable $th) {
            $toursBase->query("INSERT INTO log_search_tours (`id`, `error`, `date_error`, `operator_name`, `operator_id`, `country_oute`, `country_to`, `city_oute`, `city_to`) VALUES (NULL, 'Ошибка обработки туров: ".$th->getMessage()." - (".$th->getLine().")', CURRENT_TIMESTAMP, '".$operator['name']."', '".$operator['id']."', '".$_POST['countryOute']."', '".$_POST['countryTo']."', '".$_POST['cityOute']."', '".$_POST['regionTo']."');");
            return;
        }
    }

    function getTezTours($page){
        global $toursBase;
        $data = json_decode($page, true);
        if($data['success'] == true){
            if(count($data['data']) > 0){
                $arrayTours = array();
                foreach ($data['data'] as $toursInfo) {
                    $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$toursInfo[7][0]."'");
                    if($searchPitanie->num_rows > 0){
                        $searchPitanie = $searchPitanie->fetch_assoc();
                        if(empty($searchPitanie['new_key']) == false){
                            $toursInfo[7][0] = $searchPitanie['new_key'];
                        }
                    }else{
                        $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$toursInfo[7][0]."', '', '0');");
                    }
                    
                    array_push($arrayTours, array(
                        'date'=>$toursInfo[0].', '.$toursInfo[2], 
                        'parametrsName'=>implode(', ', $toursInfo[5]),
                        "countNight"=>$toursInfo[3], 
                        "hotel"=>$toursInfo[6][1],
                        "hotel-link"=>$toursInfo[6][0],
                        "imageHotel"=>$toursInfo[6][2],
                        "pitanie"=>$toursInfo[7][0],
                        "roomType"=>$toursInfo[8][1],
                        "price"=>$toursInfo[10]['total'],   
                        "priceOld"=>'',   
                        "bronLink"=>$toursInfo[11][0][0],
                        "typePrice"=>'',
                        "transport"=>'econom',
                        "data-townfrom"=>'',
                        "data-state"=>'',
                        "data-checkin"=>'',
                        "data-nights"=>'',
                        "data-hnights"=>'',
                        "data-cat-claim"=>'',
                        "data-packet-type"=>'',
                        "data-hotel"=>'',
                        "data-statefrom"=>''
                    ));
                }
                $page = array(
                    "type"=>true,
                    "tours"=> $arrayTours,
                );
            }else{
                $page = array(
                    "type"=>true,
                    "mess"=>'Нет данных!',
                    "tours"=> array(),
                );
            }
        }else{
            $page = array(
                "type"=>false,
                "mess"=>$data['message'],
            );
        }
        return $page;
    }

    function getPegasTours($page){
        global $toursBase;
        if(stripos($page, 'Результаты не найдены')){
            $page = array(
                "type"=>false,
                "mess"=>'Результаты не найдены!'
            );
        }else{
            $doc = hQuery::fromHTML($page);
            $body = $doc->find('tbody');
            if($body){
                if(count($body) > 0){
                    $body = $body[0];
                    $tours = $body->find('tr');
                    if($tours){
                        if(count($tours) > 0){
                            $arrayTours = array();
                            foreach ($tours as $tours) {
                                $td = $tours->find('td');
                                if($td){
                                    if(count($td) > 5){
                                        $oldPrice = $td[10]->find('span.without-reductions');
                                        if($oldPrice){
                                            if(count($oldPrice)>0){
                                                $oldPrice = $oldPrice[0]->text();
                                            }
                                        }
                                        $pitanie = $td[7]->text();
                                        $roomType = $td[6]->text();
                                        $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$pitanie."'");
                                        if($searchPitanie->num_rows > 0){
                                            $searchPitanie = $searchPitanie->fetch_assoc();
                                            if(empty($searchPitanie['new_key']) == false){
                                                $pitanie = $searchPitanie['new_key'];
                                            }
                                        }else{
                                            $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$pitanie."', '', '0');");
                                        }

                                        
                                        array_push($arrayTours, array(
                                            'date'=>$td[0]->find('span.no-wrap')[0]->text(), 
                                            'parametrsName'=>$td[2]->find('div.package-name')[0]->text(),
                                            "countNight"=>$td[3]->text(),  
                                            "hotel"=>$td[5]->find('a')[0]->text(),
                                            "hotel-link"=>$td[5]->find('a')[0]->attr('href', true),
                                            "imageHotel"=>'', 
                                            "pitanie"=>$pitanie,
                                            "roomType"=>$roomType,
                                            "price"=>preg_replace("/[^0-9]/", '', $td[10]->find('a.button')[0]->text()),   
                                            "priceOld"=>preg_replace("/[^0-9]/", '', $oldPrice),    
                                            "bronLink"=>'https://pegasys.kz.pegast.asia/PackageBooking'.explode('/PackageBooking', $td[10]->find('a.button')[0]->attr('href', true))[1],
                                            "typePrice"=>'',
                                            "transport"=>$td[9]->find('div.outgoing')[0]->attr('title', true).', '.$td[9]->find('div.return')[0]->attr('title', true),
                                            "data-townfrom"=>'',
                                            "data-state"=>'',
                                            "data-checkin"=>'',
                                            "data-nights"=>'',
                                            "data-hnights"=>'',
                                            "data-cat-claim"=>'',
                                            "data-packet-type"=>'',
                                            "data-hotel"=>'',
                                            "data-statefrom"=>''
                                        ));
                                    }
                                }
                            }
                            $page = array(
                                "type"=>true,
                                "tours"=>$arrayTours
                            );
                        }else{
                            $page = array(
                                "type"=>true,
                                "mess"=>'Нет туров!',
                                "tours"=> array()
                            );
                        }
                    }else{
                        $page = array(
                            "type"=>false,
                            "mess"=>'Не найден <tr> на странице!'
                        );
                    }
                }else{
                    $page = array(
                        "type"=>false,
                        "mess"=>'Не найден <body>!',
                        "tours"=> array()
                    );
                }
            }else{
                $page = array(
                    "type"=>false,
                    "mess"=>'Не найден <body>!',
                );
            }
        }
        return $page;
    }

    function getToursSamoAnex($page){
        global $toursBase;
        $page = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $page);
        $old_page = $page;
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page,$data);
        $page = $data[1][0];

    
        $page = htmlspecialchars_decode(stripcslashes(htmlentities($page)));
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page, $table);

    

        foreach ($table[1] as $searchTable) {
            if(stripos($searchTable, '<table class="res">')){
                $page = substr(substr($searchTable, 2),0,-2);
            }else{
                if(stripos($searchTable, 'Нет данных. Уточните параметры поиска.')){
                    $page = false;
                }
            }
        }
        if($page){
            $doc = hQuery::fromHTML($page);
            $body = $doc->find('tbody');
        
            if($body){
                if(count($body) > 0){
                    $body = $body[0];
                    $tours = $body->find('tr');
                    if($tours){
                        if(count($tours) > 0){
                            $arrayTours = array();
                            foreach ($tours as $tours) {
                                $attr = $tours;
                                $tours = $tours->find('td');
                                $hotel = $tours[5]->find('a');
                                $hotelText = '';
                                $hotelLink = '';
                                if($hotel){
                                    if(count($hotel) > 0){
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($hotel[0]->text()))));
                                        $hotelLink = $hotel[0]->attr('href', true);
                                    }else{
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[5]->text()))));
                                    }
                                }else{
                                    $hotelText = preg_replace('/&(amp;)?(.+?);/', '', trim(explode(' (', str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[5]->html())))))[0]));
                                }
                                

                                $typePrice = '';
                                if(empty($tours[13]) == false){
                                    $typePrice = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[13]->text()))));
                                }

                                $transport = '';
                                if(empty($tours[14]) == false){
                                    $transport = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[14]->text()))));
                                }
                                
                                if(count($tours) > 10){
                                    $pitanie = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[7]->text()))));
                                    $roomType = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[8]->text()))));
                                    $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$pitanie."'");
                                    if($searchPitanie->num_rows > 0){
                                        $searchPitanie = $searchPitanie->fetch_assoc();
                                        if(empty($searchPitanie['new_key']) == false){
                                            $pitanie = $searchPitanie['new_key'];
                                        }
                                    }else{
                                        $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$pitanie."', '', '0');");
                                    }


                                    $countDays = 0;
                                    preg_match('/<span[^>]*>(.*)<\/span>/Ui', $tours[4], $matches);
                                    if (isset($matches[1])) {
                                        $countDays = preg_replace("/[^0-9]/", '', $matches[1]);
                                    }

                                    $itogedNight = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags(preg_replace('/<span[^>]*>(.*)<\/span>/Ui', '', $tours[4])))));

                                    $itogedNight = $itogedNight + $countDays;


                                    
                                    array_push($arrayTours, array(
                                        'date'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[2]->text())))), 
                                        'parametrsName'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[3]->text())))),
                                        "countNight"=>$itogedNight,
                                        "hotel"=>$hotelText,
                                        "hotel-link"=>$hotelLink,
                                        "imageHotel"=>'',
                                        "pitanie"=>$pitanie,
                                        "roomType"=>$roomType, 
                                        "price"=>str_replace('/', '', preg_replace("/[^0-9]/", '', $tours[11]->text())),   
                                        "priceOld"=>'',
                                        "bronLink"=>'',
                                        "typePrice"=>$typePrice,
                                        "transport"=>$transport,
                                        "data-townfrom"=>$attr->attr("data-townfrom",true),
                                        "data-state"=>$attr->attr("data-state",true),
                                        "data-checkin"=>$attr->attr("data-checkin",true),
                                        "data-nights"=>$attr->attr("data-nights",true),
                                        "data-hnights"=>$attr->attr("data-hnights",true),
                                        "data-cat-claim"=>$attr->attr("data-cat-claim",true),
                                        "data-packet-type"=>$attr->attr("data-packet-type",true),
                                        "data-hotel"=>$attr->attr("data-hotel",true),
                                        "data-statefrom"=>$attr->attr("data-statefrom",true)
                                    ));
                                }
                            }
                            $page = array(
                                "type"=>true,
                                "tours"=>$arrayTours
                            );
                        }else{
                            $page = array(
                                "type"=>true,
                                "mess"=>'Нет туров!',
                                "tours"=>array()
                            );
                        }
                    }else{
                        $page = array(
                            "type"=>true,
                            "mess"=>'Нет туров!',
                            "tours"=>array()
                        );
                    }
                }else{
                    $page = array(
                        "type"=>false,
                        "mess"=>'Не найден <tbody>!'
                    );
                }
            }else{
                $page = array(
                    "type"=>false,
                    "mess"=>'Не найден <tbody>!'
                );
            }
        }else{
            $page = array(
                "type"=>true,
                "mess"=>'Пустая страница',
                "tours"=>array()
            );
        }
        return $page;
    }

    function getToursSamoJoin($page){
        global $toursBase;
        $page = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $page);
        $old_page = $page;
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page,$data);
        $page = $data[1][0];

    
        $page = htmlspecialchars_decode(stripcslashes(htmlentities($page)));
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page, $table);

    

        foreach ($table[1] as $searchTable) {
            if(stripos($searchTable, '<table class="res">')){
                $page = substr(substr($searchTable, 2),0,-2);
            }else{
                if(stripos($searchTable, 'Нет данных. Уточните параметры поиска.')){
                    $page = false;
                }
            }
        }
        if($page){
            $doc = hQuery::fromHTML($page);
            $body = $doc->find('tbody');
        
            if($body){
                if(count($body) > 0){
                    $body = $body[0];
                    $tours = $body->find('tr');
                    if($tours){
                        if(count($tours) > 0){
                            $arrayTours = array();
                            foreach ($tours as $tours) {
                                $attr = $tours;
                                $tour = $tours->find('td');
                                $hotel = $tour[4]->find('a');
                                $hotelText = '';
                                $hotelLink = '';
                                if($hotel){
                                    if(count($hotel) > 0){
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($hotel[0]->text()))));
                                        $hotelLink = $hotel[0]->attr('href', true);
                                    }else{
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[4]->text()))));
                                    }
                                }else{
                                    $hotelText = preg_replace('/&(amp;)?(.+?);/', '', trim(explode(' (', str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[4]->html())))))[0]));
                                }

                                

                                $typePrice = '';
                                if(empty($tour[8]) == false){
                                    $typePrice = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[8]->text()))));
                                }

                                $transport = '';
                                if(empty($tour[10]) == false){
                                    $transport = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[10]->text()))));
                                }
                                
                                if(count($tour) > 10){
                                    $pitanie = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[5]->text()))));
                                    $roomType = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[6]->text()))));
                                    $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$pitanie."'");
                                    if($searchPitanie->num_rows > 0){
                                        $searchPitanie = $searchPitanie->fetch_assoc();
                                        if(empty($searchPitanie['new_key']) == false){
                                            $pitanie = $searchPitanie['new_key'];
                                        }
                                    }else{
                                        $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$pitanie."', '', '0');");
                                    }

                                    $countDays = 0;
                                    preg_match('/<span[^>]*>(.*)<\/span>/Ui', $tours[3], $matches);
                                    if (isset($matches[1])) {
                                        $countDays = preg_replace("/[^0-9]/", '', $matches[1]);
                                    }

                                    $itogedNight = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags(preg_replace('/<span[^>]*>(.*)<\/span>/Ui', '', $tours[3])))));

                                    $itogedNight = $itogedNight + $countDays;


                                    array_push($arrayTours, array(
                                        'date'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[0]->text())))), 
                                        'parametrsName'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tour[1]->text())))),
                                        "countNight"=>$itogedNight,
                                        "hotel"=>$hotelText,
                                        "hotel-link"=>$hotelLink,
                                        "imageHotel"=>'',
                                        "pitanie"=>$pitanie,
                                        "roomType"=>$roomType,
                                        "price"=>str_replace('/', '', preg_replace("/[^0-9]/", '', $tour[9]->text())),   
                                        "priceOld"=>'',
                                        "bronLink"=>'',
                                        "typePrice"=>$typePrice,
                                        "transport"=>$transport,
                                        "data-townfrom"=>$attr->attr("data-townfrom",true),
                                        "data-state"=>$attr->attr("data-state",true),
                                        "data-checkin"=>$attr->attr("data-checkin",true),
                                        "data-nights"=>$attr->attr("data-nights",true),
                                        "data-hnights"=>$attr->attr("data-hnights",true),
                                        "data-cat-claim"=>$attr->attr("data-cat-claim",true),
                                        "data-packet-type"=>$attr->attr("data-packet-type",true),
                                        "data-hotel"=>$attr->attr("data-hotel",true),
                                        "data-statefrom"=>$attr->attr("data-statefrom",true)
                                    ));
                                }
                            }
                            $page = array(
                                "type"=>true,
                                "tours"=>$arrayTours
                            );
                        }else{
                            $page = array(
                                "type"=>true,
                                "mess"=>'Нет туров!',
                                "tours"=>array()
                            );
                        }
                    }else{
                        $page = array(
                            "type"=>true,
                            "mess"=>'Нет туров!',
                            "tours"=>array()
                        );
                    }
                }else{
                    $page = array(
                        "type"=>false,
                        "mess"=>'Не найден <tbody>!'
                    );
                }
            }else{
                $page = array(
                    "type"=>false,
                    "mess"=>'Не найден <tbody>!'
                );
            }
        }else{
            $page = array(
                "type"=>true,
                "mess"=>'Пустая страница',
                "tours"=>array()
            );
        }
        return $page;
    }

    function getToursSamo($page){
        global $toursBase;

        

        $page = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $page);

        $pagesDontLoad = $page;

        $old_page = $page;
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page,$data);
        $page = $data[1][0];

    
        $page = htmlspecialchars_decode(stripcslashes(htmlentities($page)));
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page, $table);

    

        foreach ($table[1] as $searchTable) {
            if(stripos($searchTable, '<table class="res">')){
                $page = substr(substr($searchTable, 2),0,-2);
            }else{
                if(stripos($searchTable, 'Нет данных. Уточните параметры поиска.')){
                    $page = false;
                }
            }
        }
        if($page){
            $doc = hQuery::fromHTML($page);
            $body = $doc->find('tbody');
        
            if($body){
                if(count($body) > 0){
                    $body = $body[0];
                    $tours = $body->find('tr');
                    if($tours){
                        if(count($tours) > 0){
                            $arrayTours = array();
                            foreach ($tours as $tours) {
                                $attr = $tours;
                                $tours = $tours->find('td'); 
                                $hotel = $tours[4]->find('a');
                                $hotelText = '';
                                $hotelLink = '';
                                if($hotel){
                                    if(count($hotel) > 0){
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($hotel[0]->text()))));
                                        $hotelLink = $hotel[0]->attr('href', true);
                                    }else{
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[4]->text()))));
                                    }
                                }else{
                                    $hotelText = preg_replace('/&(amp;)?(.+?);/', '', trim(explode(' (', str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[4]->html())))))[0]));
                                }

                                

                                $typePrice = '';
                                if(empty($tours[12]) == false){
                                    $typePrice = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[12]->text()))));
                                }

                                $transport = '';
                                if(empty($tours[13]) == false){
                                    $transport = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[13]->text()))));
                                }
                                
                                if(count($tours) > 10){
                                    $pitanie = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[6]->text()))));
                                    $roomType = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[7]->text()))));
                                    $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$pitanie."'");
                                    if($searchPitanie->num_rows > 0){
                                        $searchPitanie = $searchPitanie->fetch_assoc();
                                        if(empty($searchPitanie['new_key']) == false){
                                            $pitanie = $searchPitanie['new_key'];
                                        }
                                    }else{
                                        $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$pitanie."', '', '0');");
                                    }

                                    $countDays = 0;
                                    preg_match('/<span[^>]*>(.*)<\/span>/Ui', $tours[3], $matches);
                                    if (isset($matches[1])) {
                                        $countDays = preg_replace("/[^0-9]/", '', $matches[1]);
                                    }

                                    $itogedNight = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags(preg_replace('/<span[^>]*>(.*)<\/span>/Ui', '', $tours[3])))));

                                    $itogedNight = $itogedNight + $countDays;


                                    array_push($arrayTours, array(
                                        'date'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[1]->text())))), 
                                        'parametrsName'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[2]->text())))),
                                        "countNight"=>$itogedNight,
                                        "hotel"=>$hotelText,
                                        "hotel-link"=>$hotelLink,
                                        "imageHotel"=>'',
                                        "pitanie"=>$pitanie,
                                        "roomType"=>$roomType, 
                                        "price"=>str_replace('/', '', preg_replace("/[^0-9]/", '', $tours[10]->text())),   
                                        "priceOld"=>'',
                                        "bronLink"=>'',
                                        "typePrice"=>$typePrice,
                                        "transport"=>$transport,
                                        "data-townfrom"=>$attr->attr("data-townfrom",true),
                                        "data-state"=>$attr->attr("data-state",true),
                                        "data-checkin"=>$attr->attr("data-checkin",true),
                                        "data-nights"=>$attr->attr("data-nights",true),
                                        "data-hnights"=>$attr->attr("data-hnights",true),
                                        "data-cat-claim"=>$attr->attr("data-cat-claim",true),
                                        "data-packet-type"=>$attr->attr("data-packet-type",true),
                                        "data-hotel"=>$attr->attr("data-hotel",true),
                                        "data-statefrom"=>$attr->attr("data-statefrom",true)
                                    ));
                                }
                            }
                            $page = array(
                                "type"=>true,
                                "tours"=>$arrayTours,
                            );
                        }else{
                            $page = array(
                                "type"=>true,
                                "mess"=>'Нет туров!',
                                "page" => $pagesDontLoad,
                                "tours"=>array()
                            );
                        }
                    }else{
                        $page = array(
                            "type"=>true,
                            "mess"=>'Нет туров!',
                            "page" => $pagesDontLoad,
                            "tours"=>array()
                        );
                    }
                }else{
                    $page = array(
                        "type"=>false,
                        "page" => $pagesDontLoad,
                        "mess"=>'Не найден <tbody>!'
                    );
                }
            }else{
                $page = array(
                    "type"=>false,
                    "page" => $pagesDontLoad,
                    "mess"=>'Не найден <tbody>!',
                );
            }
        }else{
            $page = array(
                "type"=>true,
                "mess"=>'Пустая страница - '.$pagesDontLoad.' - '.$page,
                "page" => $pagesDontLoad,
                "tours"=>array()
            );
        }
        return $page;
    }


    function getToursSamoFunSun($page){
        global $toursBase;

        

        $page = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $page);

        $pagesDontLoad = $page;

        $old_page = $page;
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page,$data);
        $page = $data[1][0];

    
        $page = htmlspecialchars_decode(stripcslashes(htmlentities($page)));
        preg_match_all("/\((((?>[^()]+)|(?R))*)\)/", $page, $table);

    

        foreach ($table[1] as $searchTable) {
            if(stripos($searchTable, '<table class="res">')){
                $page = substr(substr($searchTable, 2),0,-2);
            }else{
                if(stripos($searchTable, 'Нет данных. Уточните параметры поиска.')){
                    $page = false;
                }
            }
        }
        if($page){
            $doc = hQuery::fromHTML($page);
            $body = $doc->find('tbody');
        
            if($body){
                if(count($body) > 0){
                    $body = $body[0];
                    $tours = $body->find('tr');
                   


                    if($tours){
                        if(count($tours) > 0){ 
                            $arrayTours = array();
                            foreach ($tours as $tours) {
                                
                                


                                $attr = $tours;
                                $tours = $tours->find('td'); 
                                $hotel = $tours[4]->find('a');

                                $priceTG = str_replace('/', '', preg_replace("/[^0-9]/", '', $tours[9]->text()));

                                
                                $hotelText = '';
                                $hotelLink = '';
                                if($hotel){
                                    if(count($hotel) > 0){
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($hotel[0]->text()))));
                                        $hotelLink = $hotel[0]->attr('href', true);
                                    }else{
                                        $hotelText = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[4]->text()))));
                                    }
                                }else{
                                    $hotelText = preg_replace('/&(amp;)?(.+?);/', '', trim(explode(' (', str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[4]->html())))))[0]));
                                }

                                

                                $typePrice = '';
                                if(empty($tours[12]) == false){
                                    $typePrice = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[12]->text()))));
                                }

                                $transport = '';
                                if(empty($tours[13]) == false){
                                    $transport = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[13]->text()))));
                                }
                                
                                if(count($tours) > 10){
                                    $pitanie = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[6]->text()))));
                                    $roomType = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[7]->text()))));
                                    $searchPitanie = $toursBase->query("SELECT * FROM dict_pitanie WHERE old_key LIKE '".$pitanie."'");
                                    if($searchPitanie->num_rows > 0){
                                        $searchPitanie = $searchPitanie->fetch_assoc();
                                        if(empty($searchPitanie['new_key']) == false){
                                            $pitanie = $searchPitanie['new_key'];
                                        }
                                    }else{
                                        $toursBase->query("INSERT IGNORE INTO dict_pitanie (`id`, `old_key`, `new_key`, `checked`) VALUES (NULL, '".$pitanie."', '', '0');");
                                    }

                                    $countDays = 0;
                                    preg_match('/<span[^>]*>(.*)<\/span>/Ui', $tours[3], $matches);
                                    if (isset($matches[1])) {
                                        $countDays = preg_replace("/[^0-9]/", '', $matches[1]);
                                    }

                                    $itogedNight = str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags(preg_replace('/<span[^>]*>(.*)<\/span>/Ui', '', $tours[3])))));

                                    $itogedNight = $itogedNight + $countDays;

                                    

                                    array_push($arrayTours, array(
                                        'date'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[1]->text())))), 
                                        'parametrsName'=>str_replace('/', '', preg_replace('/\s\s+/', ' ', trim(strip_tags($tours[2]->text())))),
                                        "countNight"=>$itogedNight,
                                        "hotel"=>$hotelText,
                                        "hotel-link"=>$hotelLink,
                                        "imageHotel"=>'',
                                        "pitanie"=>$pitanie,
                                        "roomType"=>$roomType, 
                                        "price" => $priceTG,   
                                        "priceOld"=>'',
                                        "bronLink"=>'',
                                        "typePrice"=>$typePrice,
                                        "transport"=>$transport,
                                        "data-townfrom"=>$attr->attr("data-townfrom",true),
                                        "data-state"=>$attr->attr("data-state",true),
                                        "data-checkin"=>$attr->attr("data-checkin",true),
                                        "data-nights"=>$attr->attr("data-nights",true),
                                        "data-hnights"=>$attr->attr("data-hnights",true),
                                        "data-cat-claim"=>$attr->attr("data-cat-claim",true),
                                        "data-packet-type"=>$attr->attr("data-packet-type",true),
                                        "data-hotel"=>$attr->attr("data-hotel",true),
                                        "data-statefrom"=>$attr->attr("data-statefrom",true)
                                    ));
                                }
                            }
                            $page = array(
                                "type"=>true,
                                "tours"=>$arrayTours,
                            );
                        }else{
                            $page = array(
                                "type"=>true,
                                "mess"=>'Нет туров!',
                                "page" => $pagesDontLoad,
                                "tours"=>array()
                            );
                        }
                    }else{
                        $page = array(
                            "type"=>true,
                            "mess"=>'Нет туров!',
                            "page" => $pagesDontLoad,
                            "tours"=>array()
                        );
                    }
                }else{
                    $page = array(
                        "type"=>false,
                        "page" => $pagesDontLoad,
                        "mess"=>'Не найден <tbody>!'
                    );
                }
            }else{
                $page = array(
                    "type"=>false,
                    "page" => $pagesDontLoad,
                    "mess"=>'Не найден <tbody>!',
                );
            }
        }else{
            $page = array(
                "type"=>true,
                "mess"=>'Пустая страница - '.$pagesDontLoad.' - '.$page,
                "page" => $pagesDontLoad,
                "tours"=>array()
            );
        }
        return $page;
    }
?>