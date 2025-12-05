<?php
    function responceApi($type = false, $message = '', $code = 0, $data = array()){
        echo json_encode(
            array(
                "succ"=>$type,
                "mess"=> $message,
                "code"=>$code,
                "data"=>$data,
            )
        );
        exit();
    }
?>