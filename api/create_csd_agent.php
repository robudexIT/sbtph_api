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
  if(!empty($data->extension) && !empty($data->name) && !empty($data->email) ) {

  		//set values
  		$csd->extension = $data->extension;
  		$csd->username = $data->name;
  		$csd->email = $data->email;

  		if($csd->createAgent()){
			
			$stmnt = $csd->getCallType($csd->extension) ;
			$num = $stmnt->rowCount(); 

			if($num != 0){
				//set response code - 201 created
				http_response_code(201);
	
				echo json_encode(array("message" => "CSD--Agent was added"));
			}else{
				if($csd->agentCalltype()){
					//set response code - 201 created
					http_response_code(201);
	
					echo json_encode(array("message" => "CSD--Agent was added"));
				}
			}
    		

  		}else{
  			//set response code to 503
  			http_response_code(503);
            
  			echo json_encode(array("message" => "Unable to add new Agent.All fields must not empty"));
  		}
  }else{

  	// set response code - 400 bad request

  	echo json_encode(array("message" => "Unable to add new Agent.All fields must not empty"));
  }


