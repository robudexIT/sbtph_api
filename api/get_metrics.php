<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';
include_once '../objects/collection.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);
$collection = new Collection($db);

 if(isset($_GET['group']) && isset($_GET['start_date_and_time']) && isset($_GET['end_date_and_time'])  && isset($_GET['option_metrics']) && isset($_GET['duration_weight']) && isset($_GET['callcount_weight'])){
      $option = $_GET['group'];
      $option_metrics = $_GET['option_metrics'];
       $startDateAndTime  = $_GET['start_date_and_time'];
      $startDateAndTime = trim($startDateAndTime);
     $startDateAndTime = str_replace(":", "", $startDateAndTime);
     $startDateAndTime = str_replace("-", "", $startDateAndTime);
      $startDateAndTime = str_replace(" ", "-", $startDateAndTime);
     //$startDateAndTime = $startDateAndTime.str_replace(" ", "-");
      $endDateAndTime   = $_GET['end_date_and_time'];
      $endDateAndTime = trim($endDateAndTime);
      $endDateAndTime = str_replace(":", "", $endDateAndTime);
     $endDateAndTime = str_replace("-", "", $endDateAndTime);
     $endDateAndTime = str_replace(" ", "-", $endDateAndTime);
     
    if($option == 'collection'){
       $stmnt = $collection->genMetrics($option,$startDateAndTime,$endDateAndTime,$_GET['start_date_and_time'],$_GET['end_date_and_time'],$_GET['duration_weight'],$_GET['callcount_weight'],$option_metrics);
    }else{
       $stmnt = $csd->genMetrics($option,$startDateAndTime,$endDateAndTime,$_GET['start_date_and_time'],$_GET['end_date_and_time'],$_GET['duration_weight'],$_GET['callcount_weight'],$option_metrics);
    }
   
 }else{
 	 echo 'test';
 }
