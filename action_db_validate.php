<?php

    $host = 'localhost';
    $db_name = 'dfygmb';
    $user_name = 'root';
    $db_pass = '';

    $mysqli = mysqli_connect($host,$user_name,$db_pass, $db_name); 
    if (!$mysqli) {
       echo json_encode(array('status' => 'fail'));   
    }
    else{
        echo json_encode(array('status' => 'success')); 
    }


?>