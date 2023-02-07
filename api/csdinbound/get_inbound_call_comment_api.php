<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/csdinbound.php';

$database = new Database();
$db = $database->getConnection();

$csdinbound = new CSDINBOUND($db);


if(isset($_GET['extension']) && isset($_GET['getdate']) && isset($_GET['starttimestamp'])){
    $extension = $_GET['extension'];
	$getdate = $_GET['getdate'];
	$starttimestamp = $_GET['starttimestamp'];
}
$stmnt = $csdinbound->getInboundCallComment($extension,$getdate,$starttimestamp);