<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// // database connection will be here...

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);
 // get posted data
 $data = json_decode(file_get_contents("php://input"));

 $extension = htmlspecialchars($data->extension) ; 


if( $extension != "") {
	
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