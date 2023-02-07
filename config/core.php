<?php
// show error reporting
error_reporting(E_ALL);
 
// set your default time-zone
date_default_timezone_set('Asia/Manila');
 
// variables used for jwt
$key = "sbtphilippines_key";
$iss = "http://sbtjapan.com";
$aud = "http://example.com.pht";
$iat = 1356999524;
$nbf = 1357000000;
?>