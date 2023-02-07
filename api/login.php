<?php

//required headers
header("Access-Control-Allow-Origin: * ");
 header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//setting timezone
date_default_timezone_set('Asia/Tokyo');

// // database connection will be here...

// //include database and object files
include_once '../config/database.php';
include_once '../config/core.php';
include_once '../objects/credential.php';
include_once '../vendor/firebase/php-jwt/src/BeforeValidException.php';
include_once '../vendor/firebase/php-jwt/src/ExpiredException.php';
include_once '../vendor/firebase/php-jwt/src/SignatureInvalidException.php';
include_once '../vendor/firebase/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;


$database = new Database();
$db = $database->getConnection();

$userlogin = new Credential($db);

// get posted data
$data = json_decode(file_get_contents("php://input"));

//$data->extension = "6336";
//$data->secret = "20006336";

//set properties
$userlogin->extension = $data->extension;

$checkUser = $userlogin->checkUser();

if($checkUser && ($userlogin->secret === $data->secret )){
	$token = array(
       "iss" => $iss,
       "aud" => $aud,
       "iat" => $iat,
       "nbf" => $nbf,
       "data" => array(
           "extension" => $userlogin->extension,
           "name" => $userlogin->name,
           "position" => $userlogin->position,
           "blended" =>  $userlogin->blended,
           "calltype" => $userlogin->calltype
       )
    );

    //set response code
    http_response_code(200);
    //generate jwt
   // print_r($token);
    $jwt = JWT::encode($token, $key);
   echo json_encode(array("message" => "Successful login", "jwt" => $jwt));
}else{
	// set response code
    http_response_code(401);
	echo json_encode(array ("message" => "User Not exist or Invalid Password")); 
}

?>
