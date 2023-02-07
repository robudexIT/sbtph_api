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
//  // get posted data
 // $data = json_decode(file_get_contents("php://input"));

  //make sure data objec are not empties 
  $test = 1;
  if($test === 1 ) {

  		//set values
  		$csd->extension = "10001";
  		

  		if($csd->agentCalltype()){
         

				echo json_encode(array("message" => "CSDAgent was added"));

  		}else{
  			//set response code to 503
  			http_response_code(503);
            
  			echo json_encode(array("message" => "Unable to add new Agent.All fields must not empty"));
  		}
  }else{

  	// set response code - 400 bad request

  	echo json_encode(array("message" => "Unable to add new Agent.All fields must not empty"));
  }


