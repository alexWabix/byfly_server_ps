<?php
    $dir = '/var/www/www-root/data/www/byfly.kz/';
    include($dir.'config/db.php');
    include($dir.'api/defoult/resp.php');
    $domain = 'https://byfly.kz/';

    if(empty($_POST['key'])){
        responceApi($type = false, $message = 'Empty Key params. Please send your key...');
    }else{
        $searchKey = $toursBase->query("SELECT * FROM key_store WHERE key_api='".$_POST['key']."' AND date_offf > '".date('Y-m-d H:i:s')."' ORDER BY id DESC LIMIT 1");
        if($searchKey->num_rows > 0){
            $searchKey = $searchKey->fetch_assoc();
            if($searchKey['status'] == 1){
                $ip = $_SERVER['REMOTE_ADDR'];
                if($ip==$searchKey['ip_adress']){
                    if(empty($_POST['method']) == false){
                        if(file_exists('methods/'.$_POST['method'].'.php')){
                            include('methods/'.$_POST['method'].'.php');
                        }else{
                            responceApi($type = false, $message = 'This method not found...');
                        }
                    }else{
                        responceApi($type = false, $message = 'Empty method params...');
                    }
                }else{
                    responceApi($type = false, $message = 'Your ip ('.$ip.') not registered...');
                }
            }else{
                responceApi($type = false, $message = 'This KEY dont work...');
            }
        }else{ 
            responceApi($type = false, $message = 'This KEY not fount or KEY after time...');
        }
    }
?>