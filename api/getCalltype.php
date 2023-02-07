<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();
$csd = new Csd($db);

if(isset($_GET['extension'])){

	 $extension = htmlspecialchars($_GET['extension']) ; 
	 $stmnt = $csd->getCallType($extension);
	 $num = $stmnt->rowCount();

	 if($num != 0){
     $data= array();
     $row = $stmnt->fetch(PDO::FETCH_ASSOC);
     $calltype = $row['calltype'];
     array_push($data,$calltype);
     echo json_encode($data);

     }else{
		echo json_encode(array("message" => "No Details"));
	}
		

}else{
	echo json_encode(array("message" => "No Details"));
}

