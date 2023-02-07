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


$stmnt = $csd->getAllTag();
$num = $stmnt->rowCount();
$csd_tags = array();
$date = date_create();
if($num !=0){
	 while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
	 	$csd_tag = array(
	 		"tagId" => $row['tagId'],
	 		"tagtype" => $row['tagtype'],
	 		"tagname" => $row['tagname'],
	 		"createdby" => $row['createdby'],
	 		"createddate" => $row['createddate']
	 	);
	 	array_push($csd_tags, $csd_tag);
	 }
	 echo json_encode($csd_tags);
}else{
	echo "No Tags";
}