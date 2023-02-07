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
include_once '../../objects/sales.php';

$database = new Database();
$db = $database->getConnection();

$sales = new Sales($db);
// get posted data
 $data = json_decode(file_get_contents("php://input"));

$starttimestamp = htmlspecialchars($data->starttimestamp) ; 
$getdate =  htmlspecialchars($data->getdate);
$caller = htmlspecialchars($data->caller);
$comment = htmlspecialchars($data->comment);
$commentby = htmlspecialchars($data->commentby);
$tag = htmlspecialchars($data->tag);

$stmnt = $sales->putSalesCallComment($starttimestamp, $getdate, $caller,$comment,$commentby,$tag);
//$stmnt = $csd->putComment("20190920-131217", "2019-09-20", "6328", "This is updated comment");
