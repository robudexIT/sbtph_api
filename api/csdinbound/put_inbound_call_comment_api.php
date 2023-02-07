<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// // database connection will be here...

//include database and object files
include_once '../../config/database.php';
include_once '../../objects/csdinbound.php';

$database = new Database();
$db = $database->getConnection();

$csdinbound = new CSDINBOUND($db);
 // get posted data
  $data = json_decode(file_get_contents("php://input"));

$starttimestamp = htmlspecialchars($data->starttimestamp) ; 
$getdate =  htmlspecialchars($data->getdate);
$whoansweredcall = htmlspecialchars($data->whoansweredcall);
$caller = htmlspecialchars($data->caller);
$comment = htmlspecialchars($data->comment);
$commentby = htmlspecialchars($data->commentby);
$tag= htmlspecialchars($data->tag);

$stmnt = $csdinbound->putInboundCallComment($starttimestamp, $getdate, $whoansweredcall, $comment,$commentby,$tag);
//$stmnt = $csd->putComment("20200602-000009", "2020-06-02", "6308", "", "");
// echo json_encode($data);
//echo $startimestamp;