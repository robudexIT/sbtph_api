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
   
  echo $data;

  $customer_number = $data->customer_number;
  $customer_id = $data->customer_id;
  $customer_name = $data->customer;
  $updated_by = $data->update_by;
  
  $csd->deleteCustomer($customer_number,$customer_id, $customer_name,$update_by);