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


$stmnt = $collection->getAllCollectionAgents();
$num = $stmnt->rowCount();
$collection_active = array();

if($num !=0){
	 while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
	 	$collection_agent = array(
	 		"extension" => $row['extension'],
	 		"name" => $row['name'],
	 		"email" => $row['email']
	 	);
	 	array_push($collection_active, $collection_agent);
	 }
	 echo json_encode($collection_active);
}else{
	echo "No records";
}