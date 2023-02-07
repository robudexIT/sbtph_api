<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/csdinbound.php';

$database = new Database();
$db = $database->getConnection();

$csdinbound = new CSDINBOUND($db);

if( isset($_GET['number'])){

	$caller = $_GET['number'];
	

	$stmnt = $csdinbound->searchCallerDetails($caller);


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}