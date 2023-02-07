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


if(isset($_GET['extension']) && isset($_GET['getdate']) && isset($_GET['starttimestamp'])){
    $caller = $_GET['extension'];
	$getdate = $_GET['getdate'];
	$starttimestamp = $_GET['starttimestamp'];
}
$stmnt = $collection->getCollectionCallComment($caller,$getdate,$starttimestamp);