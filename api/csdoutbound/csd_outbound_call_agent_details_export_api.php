<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/csdoutbound.php';

$database = new Database();
$db = $database->getConnection();

$csdoutbound = new CSDOUTBOUND($db);

if( isset($_GET['extension']) && isset($_GET['name']) && isset($_GET['startdate'])  && isset($_GET['enddate']) && isset($_GET['tagname']) && isset($_GET['duration']) && isset($_GET['direction'])){

	$extension = $_GET['extension'];
	$name = $_GET['name'];
	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname = $_GET['tagname'];
	$duration = $_GET['duration'];
	$direction= $_GET['direction'];

	$stmnt = $csdoutbound->csdOutboundCallAgentDetailsExport($extension,$name,$startdate,$enddate,$tagname,$duration,$direction);


}elseif(isset($_GET['modalextension']) && isset($_GET['modalname']) && isset($_GET['startdate'])  && isset($_GET['enddate']) && isset($_GET['tagname']) && isset($_GET['duration']) && isset($_GET['direction'])){

	$extension = $_GET['modalextension'];
	$name = $_GET['modalname'];
	$startdate = $_GET['startdate'];
	$enddate = $_GET['enddate'];
	$tagname = $_GET['tagname'];
	$duration = $_GET['duration'];
	$direction= $_GET['direction'];

	$stmnt = $csdoutbound->csdOutboundCallAgentDetailsExport($extension,$name,$startdate,$enddate,$tagname,$duration,$direction);


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}