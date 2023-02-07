<?php

class Sales {

	//CSD class properties
	private $salesteam = "salesteam";
	private $salesteam_callsummary_table = "outbound";
	private $conn;
	public $extension;
	public $name;
	public $email;
    public $teamlead;
    private $tag = "tag";
	
   //create database connection  when this class instantiated
    public function __construct($db){
    	$this->conn = $db;
    }
    public function getAll() {
        // build query
        $query = "SELECT * FROM ".$this->salesteam."";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        //execute
        $stmnt->execute();
        return $stmnt;
    }
    public function getSingle($extension) {
        // build query
        $query = "SELECT * FROM ".$this->salesteam." WHERE extension=?";

        //prepare the query

        $stmnt = $this->conn->prepare($query);

        //bind values 
        
        $stmnt->bindParam(1,$extension);

        //execute
        $stmnt->execute();
        return $stmnt;
    }
     public function getComment($caller,$getdate,$startimestamp) {
        //build query
        $query = "SELECT * FROM  ".$this->salesteam_callsummary_table." WHERE Caller=? AND getDate=? AND StartTimeStamp=?";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values
        $stmnt->bindParam(1,$caller);
        $stmnt->bindParam(2,$getdate);
        $stmnt->bindParam(3,$startimestamp);

        $stmnt->execute();

        $num = $stmnt->rowCount();
   
        if ($num != 0 ){
              $row = $stmnt->fetch(PDO::FETCH_ASSOC);
             $sales_comment = array("comment" => $row['comment']);
             echo json_encode($sales_comment);
            
        }else{
            echo json_encode(array ("comment" => "No comment"));
        }
    }
    public function salesCallSummary($startdate,$enddate,$tagname){
    	$currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }

        
      
          $salesTags_array = array();

          try{
                $getSalesTags = $this->getTags('SALES');
                 while ($row_tag = $getSalesTags->fetch(PDO::FETCH_ASSOC) ) {
                 array_push($salesTags_array, $row_tag['tagname']);
            }
          }catch(exception $e){
            echo "Error in Fetching Tags";
          }

         

        // build the query
        $query = "SELECT * FROM ".$this->salesteam." ORDER BY teamlead ";
        
        //prepare the query
    	$stmnt = $this->conn->prepare($query);
    
    	if($stmnt->execute()){
            $sales_summary = array();
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $getAgentTotalRecords = $this->getAgentTotalRecords($startdate,$enddate,$tagname,$row['extension']);

                 //total answer calls of each agent
                $totalMadeCalls = $getAgentTotalRecords->rowCount();

                /*This section calculate the total call duration of each agents..
                  Duration field is newly added  on the table.
                  On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                */
                 $total=0;
                 while($row_calls = $getAgentTotalRecords->fetch(PDO::FETCH_ASSOC)) {
                     if($row_calls['Duration'] != 0){
                       $total = $total +  $row_calls['Duration'];
                     }else{
                        $endtime = explode("-", $row_calls['EndTimeStamp']);
                        $startime = explode("-", $row_calls['StartTimeStamp']);
                        $total = $total + ( (strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );
                     }

                 }
                // make H:m:s time format
                 $total_duration = $this->secToHR($total);
                  $getdate = '('.$startdate.')'. "-".'('.$enddate.')';
                 if(strtotime($startdate) == strtotime($enddate)){
                    $getdate = $startdate;
                 }

                 $sales_agent_summary = array(
                    "extension" => $row['extension'],
                    "name" => $row['name'],
                     "teamlead" => $row['teamlead'],
                    "total_counts" => $totalMadeCalls,
                    "total_duration" => $total_duration,
                    "getdate" => $getdate,
                    "link_details" => "calldetails/salesdetails/?extension=" . $row['extension'] . "&name=" . $row['name'] .  "&startdate=" . $startdate. "&enddate=".$enddate ."&tagname=" .$tagname
                 );

                 array_push($sales_summary, $sales_agent_summary);
             }
             $data = array();
             array_push($data,$sales_summary);
             array_push($data,$salesTags_array);
             echo json_encode($data);

        }
    

    }

       public function getAgentTotalRecords($startdate,$enddate,$tagname,$extension) {
        if($tagname == 'all'){
             //build query
            $query  = "SELECT * FROM ".$this->salesteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? ORDER BY StartTimeStamp DESC";

            //prepare the query
            $stmnt = $this->conn->prepare($query);

            //bind values from question mark (?) place holder.
             $stmnt->bindParam(1,$startdate);
              $stmnt->bindParam(2,$enddate);
             $stmnt->bindParam(3,$extension);

            //execute

           $stmnt->execute();

           return $stmnt;
        }else{
                  //build query
              $query  = "SELECT * FROM ".$this->salesteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=? ORDER BY StartTimeStamp DESC";

              //prepare the query
              $stmnt = $this->conn->prepare($query);

              //bind values from question mark (?) place holder.
               $stmnt->bindParam(1,$startdate);
                $stmnt->bindParam(2,$enddate);
               $stmnt->bindParam(3,$extension);
               $stmnt->bindParam(4,$tagname);

              //execute

             $stmnt->execute();

             return $stmnt;
        }
       

    }

     public function searchCalledNumberCallDetails($callednumber){

          $salesTags_array = array();

          try{
                $getSalesTags = $this->getTags('SALES');
                 while ($row_tag = $getSalesTags->fetch(PDO::FETCH_ASSOC) ) {
                 array_push($salesTags_array, $row_tag['tagname']);
            }
          }catch(exception $e){
            echo "Error in Fetching Tags";
          }

        $query = "SELECT * FROM " . $this->salesteam_callsummary_table . " WHERE CalledNumber=? ORDER BY getDate DESC";

         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $callednumber);


         $stmnt->execute();

         $num = $stmnt->rowCount();


        if ($num != 0 ){

            $sales_calls_details = array();

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
                $base_url = "http://211.0.128.110/callrecording/outgoing/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;



                $get_single_agent =  $this->getSingle($row['Caller']);
                if($get_single_agent->rowCount() !=0) {
                    $agent_row = $get_single_agent->fetch(PDO::FETCH_ASSOC);
                    $agent_name = $agent_row['name'];
                }else{
                    $agent_name = "SaleAgent";
                }


                 $agent = array(
                    "caller" => $agent_name,
                     "extension" => $row['Caller'],
                    "calledNumber" => $row['CalledNumber'],
                    "callStatus" => $row['CallStatus'],
                    "startime" => date( "h:i:s a",$StartTime),
                    "endtime" =>  date("h:i:s a",$EndTime),
                    "callDuration" => $duration,
                    "callrecording" => $full_url,
                    "getDate" => $row['getDate'],
                    "comment" => $row['comment'],
                    "starttimestamp" => $row['StartTimeStamp'],
                    "tag" => $row['tag']
                );
                array_push($sales_calls_details, $agent);
            }
            //http_response_code(201);
            // echo json_encode($sales_calls_details);

             $data = array();
             array_push($data,$sales_calls_details);
             array_push($data,$salesTags_array);
             echo json_encode($data);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
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
    public function salesAgentCallDetails($extension,$username,$startdate,$enddate,$tagname){

      $getsalesTags = $this->getTags('SALES');
      
      $salesTags_array = array();

      while ($row_tag = $getsalesTags->fetch(PDO::FETCH_ASSOC) ) {
         array_push($salesTags_array, $row_tag['tagname']);
      }
       if($tagname == 'all'){
          //build query
            $query  = "SELECT * FROM ".$this->salesteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? ORDER BY StartTimeStamp DESC";

            //prepare the query
            $stmnt = $this->conn->prepare($query);

            //bind values from question mark (?) place holder.
             $stmnt->bindParam(1,$startdate);
              $stmnt->bindParam(2,$enddate);
             $stmnt->bindParam(3,$extension);

            //execute
       }else{
                  //  build query
        $query  = "SELECT * FROM ".$this->salesteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=? ORDER BY StartTimeStamp DESC ";

              //prepare the query
              $stmnt = $this->conn->prepare($query);

              //bind values from question mark (?) place holder.
               $stmnt->bindParam(1,$startdate);
                $stmnt->bindParam(2,$enddate);
               $stmnt->bindParam(3,$extension);
               $stmnt->bindParam(4,$tagname);
       }
      

         $stmnt->execute();
         
         $num = $stmnt->rowCount();

        
        if ($num != 0 ){

            $sales_calls_details = array();

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
                $base_url = "http://211.0.128.110/callrecording/outgoing/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;

                $daterange = '('.$startdate.')'. "-".'('.$enddate.')';
                if(strtotime($startdate) == strtotime($enddate)){
                    $daterange  = $startdate;
                } 

                 $agent = array(
                    "name" => $username,
                    "extension" => $extension,
                    "caller" => $row['Caller'],
                    "calledNumber" => $row['CalledNumber'],
                    "callStatus" => $row['CallStatus'],
                    "startime" => date( "h:i:s a",$StartTime),
                    "endtime" =>  date("h:i:s a",$EndTime),
                    "callDuration" => $duration,
                    "callrecording" => $full_url,
                    "getDate" => $row['getDate'],
                    "comment" => $row['comment'],
                    "starttimestamp" => $row['StartTimeStamp'],
                    "tag" => $row['tag'],
                    "daterange" => $daterange
                );
                array_push($sales_calls_details, $agent);
            }
            //http_response_code(201);
            $agent_data = array();
            array_push($agent_data,$sales_calls_details);
            array_push($agent_data,$salesTags_array );
            echo json_encode($agent_data);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }


     }

  
      public function getSalesCallComment($caller,$getdate,$starttimestamp) {
          //build query
          $query = "SELECT * FROM  ".$this->salesteam_callsummary_table." WHERE Caller=? AND getDate=? AND StartTimeStamp=?";

          //prepare the query
          $stmnt = $this->conn->prepare($query);

          //bind values
          $stmnt->bindParam(1,$caller);
          $stmnt->bindParam(2,$getdate);
          $stmnt->bindParam(3,$starttimestamp);

          $stmnt->execute();

          $num = $stmnt->rowCount();

          if ($num != 0 ){
                $row = $stmnt->fetch(PDO::FETCH_ASSOC);
               $sales_comment = array("comment" => $row['comment'], "tag" => $row['tag']);
               echo json_encode($sales_comment);

          }else{
              echo json_encode(array ("comment" => "No comment"));
          }
    }
      public function putSalesCallComment($starttimestamp, $getdate, $caller, $comment,$commentby,$tag) {
        
        //build query
       $query = "UPDATE `outbound` SET `comment`='$comment', `commentby`='$commentby', `tag`='$tag' WHERE `StartTimeStamp`='$starttimestamp' AND `getDate`='$getdate' AND `Caller`='$caller'";
        //prepare query
        $stmnt = $this->conn->prepare($query);
        //excute
        $stmnt->execute();

        $count = $stmnt->rowCount();
        if($count !=0){
                 echo json_encode(array("message" => "Successfully Updated"));
        }else{
             echo json_encode(array("message" => "Update was not Successfull"));
        }
    }

  
     
      public function createSalesAgent() {
        //create query

        $query = " INSERT INTO " . $this->salesteam . " SET  extension = :extension, name = :name, email = :email, teamlead = :teamlead";

        // prepare queery
        $stmnt = $this->conn->prepare($query);

        // sanitize
        $this->extension = htmlspecialchars(strip_tags($this->extension));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->teamlead = htmlspecialchars(strip_tags($this->teamlead));

        //bind values
        $stmnt->bindParam(":extension", $this->extension);
        $stmnt->bindParam(":name", $this->name);
        $stmnt->bindParam(":email", $this->email);
        $stmnt->bindParam(":teamlead",$this->teamlead);

        //execute query
        if($stmnt->execute()){
            return true;

        }
        return false;
    }


    public function getCallType($extension) {
      //build query
      $query = "SELECT * FROM  ".$this->calltype." WHERE extension=?";

      //prepare the query
      $stmnt = $this->conn->prepare($query);

      //bind values
      $stmnt->bindParam(1,$extension);
     

      $stmnt->execute();

      return $stmnt;
 }
  public function agentCalltype(){

           //create query
       $query = " INSERT INTO " . $this->calltype . " SET  extension = :extension, calltype = :calltype";
       // prepare query
       $stmnt = $this->conn->prepare($query);

        //bind values
        $calltype = "sales";
       $stmnt->bindParam(":extension", $this->extension);
       $stmnt->bindParam(":calltype", $calltype);

       if($stmnt->execute()){
         return true;
       }else{
         return false;
       }
      
  }

     public function updateSalesAgent() {

         $query = "UPDATE `salesteam` SET `extension`='$this->extension',`name`='$this->name',`email`='$this->email',`teamlead`='$this->teamlead' WHERE `extension`='$this->extension'";
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
   
    public function deleteSalesAgent() {
    // sanitize
    $this->extension=htmlspecialchars(strip_tags($this->extension));

    
    //delete query
    $query = "DELETE FROM `salesteam` WHERE `extension`='$this->extension'";
 
    // prepare query
    $stmnt = $this->conn->prepare($query);
 
    $stmnt->execute();
 
     $count = $stmnt->rowCount();
        if($count !=0){
                 //delete the agent records  if there are.
                 $this->deleteSalesAgentRecordings($this->extension);
                 
                 echo json_encode(array("message" => "SalesAgent Successfully Deleted"));
        }else{
             echo json_encode(array("message" => "Sales Agent Cannot be Deleted"));
        }
     }
  
      private function deleteSalesAgentRecordings($extension){
            $query = "DELETE FROM `outbound` WHERE `Caller`='$extension'";

            $stmnt = $this->conn->prepare($query);

            $stmnt->execute();
            $count = $stmnt->rowCount();
           
     }

      private function secToHR($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
         return "$hours:$minutes:$seconds";
    }


}

?>
