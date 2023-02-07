<?php
//required headers
header("Access-Control-Allow-Origin:*");
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);


if(isset($_GET['extension']) && isset($_GET['username']) ) {
	$csd->extension = $_GET['extension'];
	$name = $_GET['username'];

	$stmnt = $csd->loginLogoutDetails();
	$num = $stmnt->rowCount();
	$agents_logs = array();

	if($num != 0){
		while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
			$agent =array(
				"LOG" => $row['log'],
				"DATE" => $row['logdate'],
				"TIME" => $row['logtime']
			);
			array_push($agents_logs, $agent);
		}
		echo json_encode($agents_logs);

	}else{
		echo json_encode(array("message" => "No Details"));
	}


}else{
	echo json_encode(array("message" => "No Details"));
}