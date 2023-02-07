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
 $data = json_decode(file_get_contents("php://input"));
  

  //make sure data objec are not empties 
  if(!empty($data->action) && !empty($data->performed_by) && !empty($data->description) ) {

  		//set values
  		$csd->action = $data->action;
  		$csd->performed_by = $data->performed_by;
  		$csd->description = $data->description;
      

  		if($csd->createEventLog()){

  			//set response code - 201 created
  			http_response_code(201);

  		

  		}else{
  			//set response code to 503
  			http_response_code(503);

  		
  		}
  }else{

  	// set response code - 400 bad request

  	http_response_code(503);
  }


 