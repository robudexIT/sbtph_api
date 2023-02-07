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
	$query = "UPDATE `sip_channels` SET `status`=0";
	$result = $connection->query($query);
}

function  getUpdatedActiveCalls($connection,$extension,$status){
	$query = "UPDATE `sip_channels` SET `status`='$status' WHERE `extension`='$extension'";
	$result = $connection->query($query);

}



$active_calls = shell_exec("asterisk -rx  'sip show channels' | grep  -i ACK ");
$active_calls_file = "active_calls.txt";

//create and write the active_calls channel from the file
$handle = fopen($active_calls_file, 'w');
fwrite($handle, $active_calls);
fclose($handle);


$active_calls_array = file($active_calls_file);


iniatialzeStatus($conn);
if(count($active_calls) != 0){
	foreach($active_calls_array as $line){

		//replace more than one space to just only on space to get the real extension.
		$stripped = preg_replace('/\s+/', ' ', $line);
	    
		$get_active_extension = explode(' ', $stripped);

		$extension = $get_active_extension[1];
		$status = 1;
	   // echo $extension . " ";

		getUpdatedActiveCalls($conn,$extension,$status);
		
	}
} 

$conn->close();


?>


