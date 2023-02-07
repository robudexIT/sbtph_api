<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/csdoutbound.php';

$database = new Database();
$db = $database->getConnection();

$csdoutbound = new CSDOUTBOUND($db);

if( isset($_GET['number'])){

	$callednumber = $_GET['number'];
	

	$stmnt = $csdoutbound->searchCalledNumberCallDetails($callednumber);


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}