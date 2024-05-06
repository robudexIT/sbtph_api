<?php


class CSDINBOUND {
     //CSD class properties
	private $csdinbound_table = "csdinbound";
	private $inbound_callstatus_table = "inbound_callstatus";
	private $csdoutbound = "outbound";
	private $parked_calls_tb = "waiting_calls";
	private $voicemail = "voicemail";
  private $customer_table = "customer_info";

	private $logs_table = "logs";
	private $conn;
	public $extension;
	public $callerid;
	public $username;
	public $receive_calls;
	  //add on 08222020
	public $tagtype;
	public $tagname;
	public $createdby;
	public $createddate;
	public $tagId;
	private $tag = "tag";

	  // end

  
   //create database connection  when this class instantiated
    public function __construct($db){
    	$this->conn = $db;
    }

      public function getSingle($extension) {
        // build query
        $query = "SELECT * FROM ".$this->csdinbound_table." WHERE extension=?";

        //prepare the query

        $stmnt = $this->conn->prepare($query);

        //bind values

        $stmnt->bindParam(1,$extension);

        //execute
        $stmnt->execute();
        return $stmnt;
    }

    public function csdInboundCallSummary($startdate,$enddate,$tagname){

         //added 8/28/2020
        $getInboundTags = $this->getTags('CSDINBOUND');
      
        $inboundTags_array = array();

        while ($row_tag = $getInboundTags->fetch(PDO::FETCH_ASSOC) ) {
           array_push($inboundTags_array, $row_tag['tagname']);
        }

         $currentdate = date('Y-m-d');
         // selected must not greater the current date 
        if(strtotime($startdate) > strtotime($currentdate) || strtotime($enddate) > strtotime($currentdate)){
             $error = array( "message" =>"No Records Found");
            $message = array();
            array_push($message, $error);
            echo json_encode($message);
            exit();
        }
        if(strtotime($startdate) > strtotime($enddate) ){
            $error  = array("message" => "StartDate Cannot Be greater than the Enddate" ); 
            $message = array();
            array_push($message, $error);
            echo json_encode($message);
            exit();
        }
    	//build query
    	$query = "SELECT * FROM ".$this->csdinbound_table."  ";

    	//prepare the query

    	$stmnt = $this->conn->prepare($query);

    	if($stmnt->execute()){
            $csd_inboud_calls_summary = array();
    				while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $totalAgentInboundRecords = $this->getTotalAgentInboundRecords($startdate,$enddate,$tagname,$row['extension']);

                //total answer calls of each agent
                $total_answered = $totalAgentInboundRecords->rowCount();

								/*This section calculate the total call duration of each agents..
									Duration field is newly added  on the table.
									On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
								*/
                 $total=0;
                 while($row_calls = $totalAgentInboundRecords->fetch(PDO::FETCH_ASSOC)) {
									 if($row_calls['Duration'] != 0){
										 $total = $total +  $row_calls['Duration'];
									 }else{
											$endtime = explode("-", $row_calls['EndTimeStamp']);
											$startime = explode("-", $row_calls['StartTimeStamp']);
											$total = $total + ( (strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );
									 }
				 				}
				 				//make H:m:s time format
                 $total_duration = $this->secToHR($total);
                 $getdate = '('.$startdate.')'. "-".'('.$enddate.')';
                 if(strtotime($startdate) == strtotime($enddate)){
                    $getdate = $startdate;
                 }
		            $agent = array(
		            	"extension" => $row['extension'],
		            	"name" => $row['username'],
		            	"total_counts" => $total_answered,
		            	"total_duration" => $total_duration,
		            	"getdate" => $getdate,
		                "link_details" => "calldetails/csdinbounddetails?extension=" .$row['extension'] . "&name=" .$row['username'] . "&startdate=" .$startdate . "&enddate=".$enddate ."&tagname=" .$tagname

		            );
            	array_push($csd_inboud_calls_summary, $agent);
    		}
          $final_data = array();
          array_push($final_data, $csd_inboud_calls_summary);
          array_push($final_data,$inboundTags_array);
          
    		echo json_encode($final_data);
    	}else {
    		echo json_encode(array ("message" => "No Records Found"));
    	}

    }

  

    public function getTotalAgentInboundRecords($startdate,$enddate,$tagname, $extension){

      if($tagname == 'all'){
          $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND WhoAnsweredCall=? ORDER BY StartTimeStamp DESC";
          $stmnt = $this->conn->prepare($query);

          //bind values
          $stmnt->bindParam(1,$startdate);
          $stmnt->bindParam(2,$enddate);
          $stmnt->bindParam(3,$extension);
  
          //execute
          $stmnt->execute();
          return $stmnt;
      }else{
            $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND WhoAnsweredCall=? AND tag=? ORDER BY StartTimeStamp DESC";
          $stmnt = $this->conn->prepare($query);

          //bind values
          $stmnt->bindParam(1,$startdate);
          $stmnt->bindParam(2,$enddate);
          $stmnt->bindParam(3,$extension);
          $stmnt->bindParam(4,$tagname);
  
          //execute
          $stmnt->execute();
          return $stmnt;
      }
       
    }

    public function csdInboundCallAgentDetails($extension,$username,$startdate,$enddate,$tagname){

      $getInboundTags = $this->getTags('CSDINBOUND');
      
      $inboundTags_array = array();

      while ($row_tag = $getInboundTags->fetch(PDO::FETCH_ASSOC) ) {
         array_push($inboundTags_array, $row_tag['tagname']);
      }
    

      if($tagname == 'all'){
                //build query
          $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE CallStatus='ANSWER' AND WhoAnsweredCall=? AND getDate BETWEEN ? AND ? ORDER BY StartTimeStamp DESC";

          //prepare the query
          $stmnt = $this->conn->prepare($query);

          //bind values
          $stmnt->bindParam(1,$extension);
          $stmnt->bindParam(2,$startdate);
          $stmnt->bindParam(3,$enddate);
      }else{
                 //build query
          $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE CallStatus='ANSWER' AND WhoAnsweredCall=? AND getDate BETWEEN ? AND ? AND tag=? ORDER BY StartTimeStamp DESC";

          //prepare the query
          $stmnt = $this->conn->prepare($query);

          //bind values
          $stmnt->bindParam(1,$extension);
          $stmnt->bindParam(2,$startdate);
          $stmnt->bindParam(3,$enddate);
          $stmnt->bindParam(4,$tagname);
      }
    	

    	$stmnt->execute();

    	$num = $stmnt->rowCount();

    	if ($num != 0 ){

    		   $agent_calls_details = array();

			while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
				$total=0;
				//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
				 if($row['Duration'] == ''){
						 $endtime = explode("-", $row['EndTimeStamp']);
						 $startime = explode("-", $row['StartTimeStamp']);
						 $total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

						 $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
						 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
						 $StartTime = strtotime($StartTime);
						 $EndTime = strtotime($EndTime);
				 }
				 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
				 else{
						 $total = $total + $row['Duration'];
						 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
						 $EndTime = strtotime($EndTime);
						 $StartTime =  $EndTime - $duration;
				 }
				 				$duration = $this->secToHR($total);
                //get recordings url
                $base_url = "http://211.0.128.110/callrecording/incoming/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;

                $daterange = '('.$startdate.')'. "-".'('.$enddate.')';
                if(strtotime($startdate) == strtotime($enddate)){
                    $daterange  = $startdate;
                }
                
         
        // This section query the customer details base on the  caller number. IF the caller was alredy register set the isRegistered into true. 
        
         $customer = $this->checkCustomer($row['Caller']);
				 $agent = array(
                "name" => $username,
								 "extension" => $extension,
								 "calledNumber" => $row['CalledNumber'],
								 "caller" => $row['Caller'],
								 "callStatus" => $row['CallStatus'],
                  "startime" => date( "h:i:s a",$StartTime),
                 "endtime" =>  date("h:i:s a",$EndTime),
								 "callDuration" => $duration,
                 "callrecording" => $full_url,
								 "getDate" => $row['getDate'],
                 "comment" => $row['comment'],
                 "starttimestamp" => $row['StartTimeStamp'],
                  "tag" => $row['tag'],
                   "daterange" => $daterange,
                  "isRegistered" => $customer['isRegistered'],
                  "customer_id" => $customer['customer_id'],
                  "customer_number" => $customer['customer_number'],
                  "customer_name" => $customer['customer_name'],
                  "updated_by" =>  $customer['updated_by']     
							    );
				array_push($agent_calls_details,$agent);
			}
		      array_push($agent_calls_details);
		      $agent_data = array();
		      array_push($agent_data, $agent_calls_details);
		      array_push($agent_data, $inboundTags_array);
			//http_response_code(201);
			 echo json_encode($agent_data);
    	}else{
    		echo json_encode(array ("message" => "No Records Found"));
    	}
    }

       public function searchCallerDetails($caller){
        
        $getInboundTags = $this->getTags('CSDINBOUND');
      
	      $inboundTags_array = array();

	      while ($row_tag = $getInboundTags->fetch(PDO::FETCH_ASSOC) ) {
	         array_push($inboundTags_array, $row_tag['tagname']);
	     }

        //build query
        $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE Caller=? AND CallStatus='ANSWER' ORDER BY getDate DESC";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values
        $stmnt->bindParam(1,$caller);

        $stmnt->execute();

        $num = $stmnt->rowCount();

        if ($num != 0 ){

            $search_details = array();

            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
							$total=0;
							//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
							 if($row['Duration'] == ''){
									 $endtime = explode("-", $row['EndTimeStamp']);
									 $startime = explode("-", $row['StartTimeStamp']);
									 $total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

									 $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
									 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
									 $StartTime = strtotime($StartTime);
									 $EndTime = strtotime($EndTime);
							 }
							 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
							 else{
									 $total = $total + $row['Duration'];
									 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
									 $EndTime = strtotime($EndTime);
									 $StartTime =  $EndTime - $duration;
							 }
								$duration = $this->secToHR($total);

                //get recordings url
                $base_url = "http://211.0.128.110/callrecording/incoming/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;
                $get_single_agent =  $this->getSingle($row['WhoAnsweredCall']);
                $agent_row = $get_single_agent->fetch(PDO::FETCH_ASSOC);
                 
                $customer = $this->checkCustomer($row['Caller']);
                 $agent = array(
                    "name" => $agent_row['username'],
                    "extension" => $row['WhoAnsweredCall'],
                    "calledNumber" => $row['CalledNumber'],
                    "caller" => $row['Caller'],
                    "callStatus" => $row['CallStatus'],
                    "startime" => date( "h:i:s a",$StartTime),
                    "endtime" =>  date("h:i:s a",$EndTime),
                    "callDuration" => $duration,
                    "callrecording" => $full_url,
                    "getDate" => $row['getDate'],
                    "comment" => $row['comment'],
                    "starttimestamp" => $row['StartTimeStamp'],
                    "tag" => $row['tag'],
                    "isRegistered" => $customer['isRegistered'],
                    "customer_id" => $customer['customer_id'],
                    "customer_number" => $customer['customer_number'],
                    "customer_name" => $customer['customer_name'],
                    "updated_by" =>  $customer['updated_by']                    

                );
                array_push($search_details,$agent);
            }
            //http_response_code(201);
            $final_data = array();
            array_push($final_data,$search_details);
            array_push($final_data,$inboundTags_array);
            echo json_encode($final_data);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
    }

    public function getInboundCallComment($extension,$getdate,$starttimestamp) {
        //build query
        $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE WhoAnsweredCall=? AND getDate=? AND StartTimeStamp=?";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values
        $stmnt->bindParam(1,$extension);
        $stmnt->bindParam(2,$getdate);
        $stmnt->bindParam(3,$starttimestamp);

        $stmnt->execute();

        $num = $stmnt->rowCount();
     
        if ($num != 0 ){
            $row = $stmnt->fetch(PDO::FETCH_ASSOC);
             $sales_comment = array("comment" => $row['comment'], 'commentby' => $row['commentby'], "tag" => $row['tag']);
             echo json_encode($sales_comment);

        }else{
            echo json_encode(array ("comment" => "No comment"));
        }
    }
    public function getTags($tagtype){

      //build query
      $query = "SELECT * FROM  ".$this->tag." WHERE tagtype=?";

      //prepare the query
      $stmnt = $this->conn->prepare($query);

      //bind values
      $stmnt->bindParam(1,$tagtype);
     

      $stmnt->execute();

      return $stmnt;
    }

    public function putInboundCallComment($starttimestamp, $getdate, $whoansweredcall, $comment,$commentby,$tag) {
      
       $query = "UPDATE `inbound_callstatus` SET `comment`='$comment', `commentby`='$commentby',`tag`='$tag' WHERE `StartTimeStamp`='$starttimestamp' AND `getDate`='$getdate' AND `WhoAnsweredCall`='$whoansweredcall'";
        //prepare query
        $stmnt = $this->conn->prepare($query);

       
        $stmnt->execute();

       

        $count = $stmnt->rowCount();
        if($count !=0){
                 echo json_encode(array("message" => "Successfully Updated"));
        }else{
             echo json_encode(array("message" => "Update was not Successfull"));
        }


    } 

    public function insertCustomerInfo($customer_id, $customer_number, $customer_name, $updated_by){
       $query = " INSERT INTO " . $this->customer_table  . " SET  cid = :cid, customer_number = :customer_number, customer_name = :customer_name, updated_by = :updated_by";
        // prepare queery
        $stmnt = $this->conn->prepare($query);

        // sanitize
        $customer_id = htmlspecialchars(strip_tags($customer_id));
        $customer_number= htmlspecialchars(strip_tags($customer_number));
        $customer_name= htmlspecialchars(strip_tags($customer_name));
        $updated_by = htmlspecialchars(strip_tags($updated_by));

         //bind values
         $stmnt->bindParam(":cid", $customer_id);
         $stmnt->bindParam(":customer_number", $customer_number);
         $stmnt->bindParam(":customer_name", $customer_name);
         $stmnt->bindParam(":updated_by", $updated_by);
      

        //execute query
        if($stmnt->execute()){
          return true;
        }else{
          return false;
        }
    }

    public function updateCustomerInfo($customer_id, $customer_number, $customer_name, $updated_by ) {
      // $query = "UPDATE `customer_info` SET `extension`='$extension',`username`='$name',`email`='$email' WHERE `extension`='$extension'";
      $query = "UPDATE customer_info SET cid =?, customer_number=?,customer_name=?,  updated_by=?  WHERE customer_number=?";
      //prepare query
      $stmnt = $this->conn->prepare($query);

        // sanitize
        $customer_id = htmlspecialchars(strip_tags($customer_id));
        $customer_number= htmlspecialchars(strip_tags($customer_number));
        $customer_name= htmlspecialchars(strip_tags($customer_name));
        $updated_by = htmlspecialchars(strip_tags($updated_by));

         //bind values
         $stmnt->bindParam(1, $customer_id);
         $stmnt->bindParam(2, $customer_number);
         $stmnt->bindParam(3, $customer_name);
         $stmnt->bindParam(4, $updated_by);
         $stmnt->bindParam(5, $customer_number); 

    

       //execute query
       if($stmnt->execute()){
        return true;
      }else{
        return false;
      }
      // $count = $stmnt->rowCount();
      // if($count !=0){
      //         $response->message = "Agent Info Successfully Updated";
      //         $response->extension = $extension;
      //         $response->name = $name;
      //         $response->email = $email;
      //          echo json_encode(array($response));
      // }else{
      //      echo json_encode(array("message" => "Update was not Successfull"));
      // }
      
    }

    public function getCustomerInfo($customer_id, $customer_number, $customer_name){

    }

     private function secToHR($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
         return "$hours:$minutes:$seconds";
    }

   private function checkCustomer($caller){ 
    //build query
    $query = "SELECT * FROM  ".$this->customer_table." WHERE customer_number=?";

    //prepare the query
    $stmnt = $this->conn->prepare($query);

    //bind values
    $stmnt->bindParam(1,$caller);

    $stmnt->execute();

    $num = $stmnt->rowCount();
   
    $customer = [];
 
    if ($num != 0){
      $row = $stmnt->fetch(PDO::FETCH_ASSOC);
      $customer = array("customer_id" => $row['cid'], 'customer_number' => $row['customer_number'], "customer_name" => $row['customer_name'], "updated_by" => $row['updated_by'], 'isRegistered' => true);   
    } else{
      $customer = array("customer_id" => "", 'customer_number' => $caller , "customer_name" => "", "updated_by" => "", 'isRegistered' => false);
    }
    

    return $customer;

   }


}