<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// // database connection will be here...

//include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);
 // get posted data
  $data = json_decode(file_get_contents("php://input"));

$extension = htmlspecialchars($data->extension) ; 
$name =  htmlspecialchars($data->name);
$email = htmlspecialchars($data->email);


$stmnt = $csd->updateCSDAgent($extension,$name,$email);
//$stmnt = $csd->putComment("20190920-131217", "2019-09-20", "6328", "This is updated comment");
// echo json_encode($data);
//echo $startimestamp;