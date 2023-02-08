<?php

class Database{

    //database properties

    private $host = "192.168.70.250";
    private $dbname = "sbtphcsd2";
    // private $dbname = "restore_data";
    private $username = "python";
    private $password = "sbtph@2018";

 
    private $db;

    //database method/function

    public function getConnection(){

    		$conn = NULL;
    	
    	try{
    			$conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", "$this->username", "$this->password");
    			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    	}catch (PDOExeption $e){
    		echo 'ERROR: ' . $e->getMessage();
    	}

    	$this->db = $conn;
    	return $this->db;

    }




}