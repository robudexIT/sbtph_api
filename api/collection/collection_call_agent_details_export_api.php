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


if( isset($_GET['extension']) && isset($_GET['name']) && isset($_GET['startdate'])  && isset($_GET['enddate']) && isset($_GET['tagname'])){

	$extension = $_GET['extension'];
	$name = $_GET['name'];
	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname = $_GET['tagname'];

	$stmnt =  $collection->collectionCallAgentDetailsExport($extension,$name,$startdate,$enddate,$tagname);


}elseif(isset($_GET['modalextension']) && isset($_GET['modalname']) && isset($_GET['startdate'])  && isset($_GET['enddate']) && isset($_GET['tagname'])){

	$extension = $_GET['modalextension'];
	$name = $_GET['modalname'];
	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname = $_GET['tagname'];

	$stmnt =  $collection->collectionCallAgentDetailsExport($extension,$name,$startdate,$enddate,$tagname);


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}

