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
include_once '../objects/csdinbound.php';

$database = new Database();
$db = $database->getConnection();

$csdinbound = new CSDINBOUND($db);
//  // get posted data
  $data = json_decode(file_get_contents("php://input"));

  //make sure data objec are not empties 
  if(!empty($data->customer_id) && !empty($data->customer_number) && !empty($data->customer_name) ) {

  		//set values
  		$customer_id = $data->customer_id;
  		$customer_number = $data->customer_number;
  		$customer_name = $data->customer_name;

  		if($csdinbound->updateCustomerInfo($customer_id, $customer_number, $customer_name)){
			
            http_response_code(201);
	
            echo json_encode(array("message" => "New Customer info Was Added!"));
    		

  		}else{
  			//set response code to 503
  			http_response_code(503);
            
  			echo json_encode(array("message" => "Unable to update Customer.All fields must not empty"));
  		}
  }else{

  	// set response code - 400 bad request

  	echo json_encode(array("message" => "Unable to add Update.All fields must not empty"));
  }


