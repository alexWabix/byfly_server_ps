<?php
   
    if(empty($_POST)){
        $_POST = $_GET;
    }

    $data = array(
        'type' => 'create_search',
        'startDateDay' => $_POST['startDateDay'],
        'startDateMonth' => $_POST['startDateMonth'],
        'startDateYeath' => $_POST['startDateYeath'],
        'endDateDay' => $_POST['endDateDay'],
        'endDateMonth' => $_POST['endDateMonth'],
        'endDateYearh' => $_POST['endDateYearh'],
        'endTickets' => $_POST['endTickets'],
        'aviaClass'=> $_POST['aviaClass'],
        'countAdult' => $_POST['countAdult'],
        'countChildren' => $_POST['countChildren'],
        'countInfant' => $_POST['countInfant'],
        'cityOute' => $_POST['cityOute'],
        'cityTo' => $_POST['cityTo'],
        'db_host' => $db_host,
        'db_user' => $db_user,
        'db_pass' => $db_pass,
        'db_name' => $db_name,
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:1373?".http_build_query($data));
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tickets = curl_exec($ch);
    curl_close($ch);

    if(empty($tickets) == false){
        $tickets = json_decode($tickets, true);
        echo json_encode(array(
            "type"=>true,
            "data"=>$tickets,
        ),JSON_UNESCAPED_UNICODE);
        exit;
    }else{
        echo json_encode(array(
            "type"=>false,
            "msg"=>'Server Tickets is offline! '.json_encode($data, JSON_UNESCAPED_UNICODE),
        ),JSON_UNESCAPED_UNICODE);
        exit;
    }

    

    



   
?>