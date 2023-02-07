<?php

class Credential {

	//Credential Properties

	private $logintable = "login";
        private $collectiontable = "collectionteam";
        private $csdtable = "csdinbound" ;
        private $calltype_table= "calltype";
	private $conn;
	public $extension;
	public $secret;
	public $name;
	public $position;
        public $blended;
        public $calltype;
        
    

	//create database connection  when this class instantiated
    public function __construct($db){
    	$this->conn = $db;
    }


    public function checkUser (){
    	$query = "SELECT *
            FROM " . $this->logintable . "
            WHERE extension = ?
            LIMIT 0,1";

        // prepare the query
        $stmt = $this->conn->prepare($query);

        //sanitize
      //  $this->extension = htmlspecialchars(strip_tags($this->extension));

        //bind params

        
        
        $stmt->bindParam(1, $this->extension);

        //execute

        $stmt->execute();


        //get the number of rows

        $num = $stmt->rowCount();
        $csd = 0;
        $collection = 0;
 
        if($num>0){
        	//get record details
        	$row = $stmt->fetch(PDO::FETCH_ASSOC);

        	$this->extension = $row['extension'];
        	$this->secret = $row['secret'];
        	$this->name = $row['name'];
                $this->position = $row['position'];
                $this->calltype = $this->getCallType($row['extension']);

                if($this->checkIfAgentIsInTheTable($this->csdtable) != 0){
                   $csd = 1;
		   if($this->checkIfAgentIsInTheTable($this->collectiontable) != 0){
                     $collection = 1;     
                   }
                   if($csd == 1 && $collection == 1){
                        $this->blended = "1";
                   }else{
                           $this->blended = "0";
                   }
		}elseif($this->checkIfAgentIsInTheTable($this->collectiontable) != 0){
                  $collection = 1;
                  if($this->checkIfAgentIsInTheTable($this->csdtable) != 0){
                        $csd = 1;
                  }
                  if($csd == 1 && collection == 1){
                          $this->blended = "1";
                  }else{
                          $this->blended = "0";
                  }
                }
               return true;
        } else{
        	return false;
        }

}


public function checkIfAgentIsInTheTable($table) {
	 $query = "SELECT *
         FROM " . $table . "
         WHERE extension = ?
         LIMIT 0,1";

         // prepare the query
         $stmt = $this->conn->prepare($query);
	 
        $stmt->bindParam(1, $this->extension);

        //execute
         $stmt->execute();
        //get the number of rows
        
        $num = $stmt->rowCount();
         
	 if($num>0){
               
	   return 1;
                
        } else{
          return  0;
        
	}


}

public function getCallType($extension) {
        //build query
        $query = "SELECT * FROM  ".$this->calltype_table." WHERE extension=?";
 
        //prepare the query
        $stmnt = $this->conn->prepare($query);
  
        //bind values
        $stmnt->bindParam(1,$extension);
       
  
        $stmnt->execute();
        $num = $stmnt->rowCount();

        if($num>0){
                $calltype;
                $row = $stmnt->fetch(PDO::FETCH_ASSOC);
                $calltype = $row['calltype'];
                return $calltype;
        }else{
           return false;
        }

        
}


}
?>
