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

if(isset($_GET['startdate']) && isset($_GET['enddate']) && isset($_GET['tagname'])){

	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname =  $_GET['tagname'];
}else{
	$startdate = date('Y-m-d');
	$enddate = date('Y-m-d');
	$tagname = 'all';
}


$stmnt = $collection->collectionCallSummaryExport($startdate,$enddate,$tagname);