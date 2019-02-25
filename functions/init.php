<?php

ob_start();//This is a predefined function for output buffering
session_start();//to make sure to start in all tabs and databases stuffs

include('db.php');
include('functions.php');


if($con){
    echo "Yes is connectwed";
}

?>