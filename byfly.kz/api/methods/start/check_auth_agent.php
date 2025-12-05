<?php
    $userPhone = preg_replace("/[^,.0-9]/", '', $_POST['userPhone']);
    responceApi(true, 'Success generation Responce!', 0, $userPhone);  
?> 