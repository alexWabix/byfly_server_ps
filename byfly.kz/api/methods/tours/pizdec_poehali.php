<?php
    set_time_limit(0);
    ini_set('max_execution_time',0);
    ini_set('memory_limit', '2048M');
    $dir = '/var/www/www-root/data/www/byfly.kz/';
    require_once($dir.'config/lybtrary/angry-curl/RollingCurl.class.php');
    require_once($dir.'config/lybtrary/angry-curl/AngryCurl.class.php'); 

    $listProxy = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.best-proxies.ru/proxylist.txt?key=1fd8a382d1ab65677e8c1d76c1aca154&type=http,https&limit=0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PROXY, '85.142.203.231:64040');
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'ttNkVLRS:63cYXNdr');

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31');

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        $listProxy = explode("\n", trim($response));
    }
    curl_close($ch);
    


    $realProxies = array();


    $AC = new AngryCurl('callback_function');

    $AC->init_console();
    $agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.31'; 
    if(count($listProxy) > 0){
        $i = 0;
        $proxyCount = count($listProxy);
        while ($i++ < 20000) {
            if(empty($_GET['what'])){
                $request = new AngryCurlRequest('https://travorium.com/ru/membership/');
            }else{
                if($_GET['what'] == 1){
                    $request = new AngryCurlRequest('https://travorium.com/ru/membership/');
                }else{
                    $request = new AngryCurlRequest('https://www.poehalisnami.kz/goryashie-turi?utm_source=google&utm_medium=cpc&utm_campaign=17437676877&utm_content=148802774331&utm_term=poehalisnami&gad_source=1&gclid=CjwKCAjwo6GyBhBwEiwAzQTmcyFStwGL0qS9qoDY5tb1oY6F3VFJzpCY3zjNF2cqXo4poTGunc66nRoCxjoQAvD_BwE');
                }
            }
            $proxy = $listProxy[$i % $proxyCount];

            $request->options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_VERBOSE => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PROXY => trim($proxy),
                CURLOPT_FAILONERROR => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_REFERER => "google.com",
                CURLOPT_USERAGENT => $agent
            );

            $AC->add($request);
        }

        $AC->execute(700);
    }
    $realProxies = array_unique($realProxies);
    $listProxy = $realProxies;


    if(count($listProxy) > 0){
        $i = 0;
        $proxyCount = count($listProxy);
        while ($i++ < 25000) {
            if(empty($_GET['what'])){
                $request = new AngryCurlRequest('https://travorium.com/ru/membership/');
            }else{
                if($_GET['what'] == 1){
                    $request = new AngryCurlRequest('https://travorium.com/ru/membership/');
                }else{
                    $request = new AngryCurlRequest('https://www.poehalisnami.kz/tour/turciya?did=140%2c139&atid=2&dd=29.05.2024&ddt=07.06.2024&dcid=7&dcidto=8');
                }
            }
            $proxy = $listProxy[$i % $proxyCount];

            $request->options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_VERBOSE => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PROXY => trim($proxy),
                CURLOPT_FAILONERROR => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_REFERER => "google.com",
                CURLOPT_USERAGENT => $agent
            );

            $AC->add($request);
        }

        $AC->execute(600);
    }

    unset($AC);
    function callback_function($response, $info, $request)
    {
        global $realProxies;
        if($info['http_code']!==200)
        {
            AngryCurl::add_debug_msg(
                "->\t" .
                $request->options[CURLOPT_PROXY] .
                "\tFAILED\t" .
                $info['http_code'] .
                "\t" .
                $info['total_time'] .
                "\t" .
                $info['url']
            );
        }else
        {
            array_push($request->options[CURLOPT_PROXY], options[CURLOPT_PROXY]);
            AngryCurl::add_debug_msg(
                "->\t" .
                $request->options[CURLOPT_PROXY] .
                "\tOK\t" .
                $info['http_code'] .
                "\t" .
                $info['total_time'] .
                "\t" .
                $info['url']
            );

        }
        
        return;
    }
?>