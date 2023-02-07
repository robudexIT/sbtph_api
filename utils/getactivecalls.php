<?php

$servername = "192.168.70.250";
$username = "python";
$password = "sbtph@2018";
$dbname = "sbtphcsd";
global $conn;
// Create connection
 $conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  	die("Connection failed: " . $conn->connect_error);
  
}

function iniatialzeStatus($connection){
	$query = "UPDATE `sip_channels` SET `status`=0 , `counter`=0";
	$result = $connection->query($query);
}

function  getUpdatedActiveCalls($connection,$extension,$status,$counter){
	$query = "UPDATE `sip_channels` SET `status`='$status',`counter`='$counter' WHERE `extension`='$extension'";
	$result = $connection->query($query);
   
}

function getAllExtension($connection) {
	$result = $connection->query($query);
	 return $result;
}

$active_calls = shell_exec("asterisk -rx  'sip show channels' | grep  -i ACK ");
$active_calls_file = "active_calls.txt";

//create and write the active_calls channel from the file
$handle = fopen($active_calls_file, 'w');
fwrite($handle, $active_calls);
fclose($handle);


$active_calls_array = file($active_calls_file);




$array_of_extension  = array();
if(count($active_calls) != 0){
	foreach($active_calls_array as $line){

		//replace more than one space to just only on space to get the real extension.
		$stripped = preg_replace('/\s+/', ' ', $line);
	    
		$get_active_extension = explode(' ', $stripped);

		$extension = $get_active_extension[1];
		$status = 1;
	   
	    //make array of extension
        array_push($array_of_extension, $extension);
		
		
	}
	print_r($array_of_extension);
	$query = "SELECT * FROM `sip_channels`";
	$result = $conn->query($query);

	while($row = $result->fetch_assoc()){
		if(in_array($row['extension'], $array_of_extension)){
			$extension = $row['extension'];
			$counter = $row['counter'] + 1;
			$status = 1;
			getUpdatedActiveCalls($conn,$extension,$status,$counter);

		}else{
			$extension = $row['extension'];
			$counter = 0;
			$status = 0;
			getUpdatedActiveCalls($conn,$extension,$status,$counter);
		}
	}

} else{
	iniatialzeStatus($conn);
}

$conn->close();


?>


