<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);

if( isset($_GET['customer_number']) ){

	$customer_number = $_GET['customer_number'];
	

	$stmnt = $csd->searchCustomer($customer_number);


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}