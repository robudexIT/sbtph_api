<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../../config/database.php';
include_once '../../objects/sales.php';

$database = new Database();
$db = $database->getConnection();

$sales = new Sales($db);


$stmnt = $sales->getAll();
$num = $stmnt->rowCount();
$sales_active = array();

if($num !=0){
	 while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
	 	$sales_agent = array(
	 		"extension" => $row['extension'],
	 		"name" => $row['name'],
	 		"email" => $row['email'],
	 		"teamlead" => $row['teamlead']
	 	);
	 	array_push($sales_active, $sales_agent);
	 }
	 echo json_encode($sales_active);
}else{
	echo "No records";
}