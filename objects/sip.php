<?php

class Sip {

	//CSD class properties
	private $sip_channels = "sip_channels";
	private $conn;
	public $extension;
	public $status;

	//create database connection  when this class instantiated
    public function __construct($db){
    	$this->conn = $db;
    	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    private function iniatialzeStatus() {
    	//build query
    	$query = "UPDATE ".$this->sip_channels." SET status=? ";

    	// prepare the query
    	 $stmnt = $this->conn->prepare($query);

    	 $status = 0;
    	 //bind values
    	 $stmnt->bindParam(1,$status);
    	
    	 //display error when execution is not successful.
    	  if(!$stmnt->execute()) {
    	  	print_r($conn->errorInfo());
    	  }
    	  	

    		
    }
	public function getUpdatedActiveCalls(){
		
		$this->iniatialzeStatus();

		$extension = $this->extension;
		$status = $this->status;

		//build query 
		$query = "UPDATE ".$this->sip_channels." SET status=? WHERE extension=?";

		//prepare the query
		$stmnt = $this->conn->prepare($query);
		//bin values
		$stmnt->bindParam(1,$status);
		$stmnt->bindParam(2,$extension);

		//display error when execution is not successful.
    	  if(!$stmnt->execute()) {
    	  	print_r($conn->errorInfo());
    	  }


	}




}

?>