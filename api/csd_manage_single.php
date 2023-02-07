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


if( isset($_GET['extension']) ){

	$extension = $_GET['extension'];
	

	$stmnt = $csd->getSingle($extension);
    $num = $stmnt->rowCount();
	$csd_active = array();
	if($num !=0){
	 while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
	 	$csd_active_agent = array(
	 		"extension" => $row['extension'],
	 		"name" => $row['username'],
	 		"email" => $row['email']
	 	);
	 	array_push($csd_active, $csd_active_agent);
	 }
	 echo json_encode($csd_active);
	}else{
		echo "No records";
	}


}else{

	echo json_encode(array("message" => "Each Field must not empty"));
}

