<?php

$params = json_decode($_GET['querystring']);

$channel = "SIP/" . $params->channel;
$channel_to_spy = '11' . $params->channel_to_spy;



 $socket = fsockopen("192.168.70.250","5038", $errno, $errstr, $timeout);
 fputs($socket, "Action: Login\r\n");
 fputs($socket, "UserName: sbtc2c\r\n");
 fputs($socket, "Secret: sbtrading\r\n\r\n");

 
 fputs($socket, "Action: Originate\r\n");
 fputs($socket, "Channel: $channel\r\n");
 fputs($socket, "Context: sbtph-poweruser\r\n");
 fputs($socket, "Exten: $channel_to_spy\r\n");
 fputs($socket, "Priority: 1\r\n");
 fputs($socket, "Callerid: CHANNEL_SPY\r\n");
 fputs($socket, "Variable: ISP=$isp\r\n\r\n" );
 fputs($socket, "Action: Logoff\r\n\r\n");
 
 
$wrets=fgets($socket,128);


	while (!feof($socket)) {
		
		$wrets .= fread($socket, 8192);
	}	
	//echo $wrets;


fclose($socket);

//header('Location: http://192.168.70.250/sbtph/active.php');
?>


