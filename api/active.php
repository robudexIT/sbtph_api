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

$canreceive_calls = 1;

$stmnt = $csd->active_inactive($canreceive_calls);
$num = $stmnt->rowCount();
$csd_active = array();
$log = "IN";

  function secToHR($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
         return "$hours:$minutes:$seconds";
    }
if($num !=0){
	 while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
	 	$login_duration = $csd->login_logout_duration($log,$row['extension']);
	 	$getchannelcounterStat = $csd->getActiveChannels($row['extension']);
	 	
	 	$csd_active_agent = array(
	 		"extension" => $row['extension'],
	 		"username" => $row['username'],
	 		"loginlogout" => "login_logout_details.php?extension=" .$row['extension'] . "&username=" .$row['username'],
	 		"loginduration" => $login_duration,
	 		"channelstat" => $getchannelcounterStat['status'],
	 		"counter" => $getchannelcounterStat['counter'],
			 "activecalltime" => secToHR($getchannelcounterStat['counter']),
			 "serverip" => $row['serverip'],
			 "serverstatus" => $row['serverstatus']
	 	);
	 	array_push($csd_active, $csd_active_agent);
	 }
	 echo json_encode($csd_active);
}else{
	echo "No records";
}