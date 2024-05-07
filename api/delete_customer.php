<?php
//required headers
header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// // database connection will be here...

//include database and object files
include_once '../config/database.php';
include_once '../objects/csd.php';

$database = new Database();
$db = $database->getConnection();

$csd = new Csd($db);
//  // get posted data
  $data = json_decode(file_get_contents("php://input"));
   


  $this->customer_number = $data->customer_number;
  $this->customer_id = $data->customer_id;
  $this->customer_name = $data->customer;
  $this->updated_by = $data->updated_by;
  
  $csd->deleteCustomer();