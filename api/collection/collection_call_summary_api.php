<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/collection.php';

$database = new Database();
$db = $database->getConnection();

$collection = new Collection($db);

if(isset($_GET['startdate']) && isset($_GET['enddate']) && isset($_GET['tagname']) && isset($_GET['duration']) && isset($_GET['direction'])){

	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname =  $_GET['tagname'];
	$duration = $_GET['duration'];
    $direction = $_GET['direction'];
}else{
	$startdate = date('Y-m-d');
	$enddate = date('Y-m-d');
	$tagname = 'all';
	$duration = "0";
    $direction = "UP";
}

$duration = (int) $duration;
$stmnt = $collection->collectionCallSummary($startdate,$enddate,$tagname,$duration,$direction);