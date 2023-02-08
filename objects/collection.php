<?php

include_once '../../config/config.php';
class Collection {

    //CSD class properties
    private $collectionteam = "collectionteam";
    private $collectionteam_callsummary_table = "collectionteam_callsummary";
    private $tag = "tag";
    private $calltype = "calltype";
    private $conn;
    public $extension;
    public $name;
    public $email;
   

   //create database connection  when this class instantiated
    public function __construct($db){
        $this->conn = $db;
    }

     public function genMetrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics) {
         $data = array();
         if($option_metrics == 'tag'){
                   //This code section of code is for tag metrics
               $getCollectionRecords = $this->getCollectionRecordsBaseOnDateRange($startDateAndTime,$endDateAndTime);

               //get the range of month first 
              $array_month = array();
              $total_records = 0;
             while ($row_month = $getCollectionRecords->fetch(PDO::FETCH_ASSOC)) {
                $total_records++;
                $getmonth = date("F",strtotime($row_month['getDate']));
                $getyear = date("Y",strtotime($row_month['getDate']));
                $month_year = $getmonth. "-" . $getyear;
                if(!array_key_exists($month_year,$array_month)){
                   $array_month[$month_year] = 0;
                }
             }
             $getCollectionRecords = $this->getCollectionRecordsBaseOnDateRange($startDateAndTime,$endDateAndTime);
             while($row = $getCollectionRecords->fetch(PDO::FETCH_ASSOC)){
               date("F",strtotime($row['getDate']));
                $getmonth = date("F",strtotime($row['getDate']));
                $getyear = date("Y",strtotime($row['getDate']));
                $month_year = $getmonth. "-" . $getyear;
                if($row['tag'] == ''){
                  $tag = 'NO TAG';
                }else{
                  $tag = $row['tag'];
                }
                if(array_key_exists($tag, $data)){
                  $data[$tag][$month_year] = $data[$tag][$month_year] + 1;
                }else{
                  $data[$tag] = $array_month;
                  $data[$tag][$month_year] = 1;
                }
                
             }
             $tags_options = array();
           
             $getCollectionTags = $this->getTags('COLLECTION');
              
              $collectionTags_array = array();

              while ($row_tag = $getCollectionTags->fetch(PDO::FETCH_ASSOC) ) {
                 array_push($tags_options, $row_tag['tagname']);
              }

                             
             for($t=0;$t<count($tags_options);$t++){
              if(!array_key_exists($tags_options[$t], $data)){
                $data[$tags_options[$t]] = $array_month;
              }
             } 
             $data['option_metrics'] = 'tag';
              $data['option'] = $option;
              $data['total_records'] = $total_records;
              $data['dateRange'] = $origstarttime . ' To ' . $orgendtime;
            echo json_encode($data);

              //end section of  tag metrics code
         }else{
                         // build the query
                 $query = "SELECT * FROM ".$this->collectionteam."  ";


                //prepare the query
                $stmnt = $this->conn->prepare($query);

                if($stmnt->execute()){
                        $collection_summary = array();
                        while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                            $getAgentTotalRecords = $this->getCollectionAgentTotalRecords($startDateAndTime,$endDateAndTime,$row['extension']);

                             //total answer calls of each agent
                            $totalMadeCalls = $getAgentTotalRecords->rowCount();

                            /*This section calculate the total call duration of each agents..
                              Duration field is newly added  on the table.
                              On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                            */
                             $total_sec=0;
                             while($row_calls = $getAgentTotalRecords->fetch(PDO::FETCH_ASSOC)) {
                               if($row_calls['Duration'] !=0){
                                  $total_sec = $total_sec +  $row_calls['Duration'];
                               }else{
                                 $endtime = explode("-", $row_calls['EndTimeStamp']);
                                 $startime = explode("-", $row_calls['StartTimeStamp']);
                                 $total_sec = $total_sec + ( (strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );
                               }

                             }
                            // make H:m:s time format
                              $grand_total_duration_sec = $grand_total_duration_sec + $total_sec;
                             $total_duration = $this->secToHR($total_sec);
                             $grand_total_counts = $grand_total_counts + $totalMadeCalls;

                             $collection_agent_summary = array(
                               "extension" => $row['extension'],
                              "name" => $row['name'],
                              "total_answered" => $totalMadeCalls,
                              "total_duration" => $total_duration,
                              "total_sec" => $total_sec
                             );

                          array_push($collection_summary, $collection_agent_summary);
                         }
                        $grand_total_duration = $this->secToHR($grand_total_duration_sec);
                        $grand_total = array('option' => $option, 'duration_weight' => $duration_weight,'callcount_weight' =>$callcount_weight,'grand_total_duration_sec' => $grand_total_duration_sec,'grand_total_duration' => $grand_total_duration, 'grand_total_counts' => $grand_total_counts,'datetimeRange' => $origstarttime . ' To ' . $orgendtime);
                       $final_results = array();
                       array_push($final_results, $grand_total);
                       array_push($final_results, $collection_summary);

                      echo json_encode($final_results);

                    }
                }


     }
      public function getCollectionAgentTotalRecords($startDateAndTime, $endDateAndTime, $extension) {

        //build query
        $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE  CallStatus='ANSWER'  AND Caller =? AND StartTimeStamp BETWEEN ? AND ? ORDER BY StartTimeStamp DESC ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values from question mark (?) place holder.

         $stmnt->bindParam(1,$extension);
         $stmnt->bindParam(2,$startDateAndTime);
         $stmnt->bindParam(3,$endDateAndTime);
        //execute

       $stmnt->execute();

       return $stmnt;


    }

    public function getCollectionRecordsBaseOnDateRange($startDateAndTime, $endDateAndTime) {

        //build query
        $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE  CallStatus='ANSWER'  AND StartTimeStamp BETWEEN ? AND ? ORDER BY StartTimeStamp ASC ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values from question mark (?) place holder.

         
         $stmnt->bindParam(1,$startDateAndTime);
         $stmnt->bindParam(2,$endDateAndTime);
        //execute

       $stmnt->execute();

       return $stmnt;


    }

    public function getAllCollectionAgents() {
        // build query
        $query = "SELECT * FROM ".$this->collectionteam."";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        //execute
        $stmnt->execute();
        return $stmnt;
    }
     public function getSingle($extension) {
        // build query
        $query = "SELECT * FROM ".$this->collectionteam." WHERE extension=?";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        $stmnt->bindParam(1,$extension);
        //execute
        $stmnt->execute();
        return $stmnt;
    }
    public function collectionCallSummary($startdate,$enddate,$tagname,$duration,$direction){
        $currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }
          $getCollectionTags = $this->getTags('COLLECTION');
      
          $collectionTags_array = array();

          while ($row_tag = $getCollectionTags->fetch(PDO::FETCH_ASSOC) ) {
             array_push($collectionTags_array, $row_tag['tagname']);
          }

        // build the query
        $query = "SELECT * FROM ".$this->collectionteam."  ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        if($stmnt->execute()){
            $collection_summary = array();
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $getAgentTotalRecords = $this->getAgentTotalRecords($startdate,$enddate,$tagname ,$duration,$direction,$row['extension']);

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

                 $collection_agent_summary = array(
                    "extension" => $row['extension'],
                    "name" => $row['name'],
                    "total_counts" => $totalMadeCalls,
                    "total_duration" => $total_duration,
                    "getdate" => $getdate,
                    "link_details" => "calldetails/collectiondetails?extension=" . $row['extension'] . "&name=" . $row['name'] .  "&startdate=" . $startdate. "&enddate=".$enddate ."&tagname=" .$tagname . "&duration=" . $duration . "&direction=" . $direction
                 );

                 array_push($collection_summary, $collection_agent_summary);
             }
             $data = array();
             array_push($data,$collection_summary);
             array_push($data,$collectionTags_array);
             echo json_encode($data);

        }
    }

    

    public function getAgentTotalRecords($startdate,$enddate,$tagname, $duration,$direction,$extension) {
        if($tagname == 'all'){
             //build query
            //  CONVERT(Duration, INT)>=?
            if ($direction == "UP"){
              $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND CONVERT(Duration, INT)>=?  ORDER BY StartTimeStamp DESC";
            }else{
              $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND CONVERT(Duration, INT)<=?  ORDER BY StartTimeStamp DESC";
            }
           

            //prepare the query
            $stmnt = $this->conn->prepare($query);

            //bind values from question mark (?) place holder.
             $stmnt->bindParam(1,$startdate);
             $stmnt->bindParam(2,$enddate);
             $stmnt->bindParam(3,$extension);
             $stmnt->bindParam(4,$duration);

            //execute

           $stmnt->execute();

           return $stmnt;
        }else{
                  //build query
              if($direction == "UP"){
                $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=? AND CONVERT(Duration, INT)>=?ORDER BY StartTimeStamp DESC";
              }else{
                $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=? AND CONVERT(Duration, INT)<=?ORDER BY StartTimeStamp DESC";
              }
             

              //prepare the query
              $stmnt = $this->conn->prepare($query);

              //bind values from question mark (?) place holder.
               $stmnt->bindParam(1,$startdate);
                $stmnt->bindParam(2,$enddate);
               $stmnt->bindParam(3,$extension);
               $stmnt->bindParam(4,$tagname);
               $stmnt->bindParam(5,$duration);
              //execute

             $stmnt->execute();

             return $stmnt;
        }
       

    }
     
    public function searchCalledNumberCallDetails($callednumber){

         $getCollectionTags = $this->getTags('COLLECTION');
      
          $collectionTags_array = array();

          while ($row_tag = $getCollectionTags->fetch(PDO::FETCH_ASSOC) ) {
             array_push($collectionTags_array, $row_tag['tagname']);
          }


        $query = "SELECT * FROM " . $this->collectionteam_callsummary_table . " WHERE CalledNumber=? ORDER BY getDate DESC";

         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $callednumber);


         $stmnt->execute();

         $num = $stmnt->rowCount();


        if ($num != 0 ){

            $collection_calls_details = array();

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
                array_push($collection_calls_details, $agent);
            }
            //http_response_code(201);
         //   echo json_encode($collection_calls_details);
             $data = array();
             array_push($data,$collection_calls_details);
             array_push($data,$collectionTags_array);
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
    public function collectionAgentCallDetails($extension,$username,$startdate,$enddate,$tagname,$duration,$direction){

      $getCollectionTags = $this->getTags('COLLECTION');
      
      $collectionTags_array = array();

      while ($row_tag = $getCollectionTags->fetch(PDO::FETCH_ASSOC) ) {
         array_push($collectionTags_array, $row_tag['tagname']);
      }
       if($tagname == 'all'){
          //build query
            if($direction == "UP"){
              $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND CONVERT(Duration, INT)>=? ORDER BY StartTimeStamp DESC";
            }else{
              $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND CONVERT(Duration, INT)<=? ORDER BY StartTimeStamp DESC";
            }


            //prepare the query
            $stmnt = $this->conn->prepare($query);

            //bind values from question mark (?) place holder.
             $stmnt->bindParam(1,$startdate);
              $stmnt->bindParam(2,$enddate);
             $stmnt->bindParam(3,$extension);
             $stmnt->bindParam(4,$duration);

            //execute
       }else{
                  //  build query
              if($direction == "UP"){
                $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=?  AND CONVERT(Duration, INT)>=? ORDER BY StartTimeStamp DESC ";
              }else{
                $query  = "SELECT * FROM ".$this->collectionteam_callsummary_table." WHERE getDate BETWEEN ? AND ? AND CallStatus='ANSWER'  AND Caller =? AND tag=?  AND CONVERT(Duration, INT)<=? ORDER BY StartTimeStamp DESC ";
              }
             

              //prepare the query
              $stmnt = $this->conn->prepare($query);

              //bind values from question mark (?) place holder.
               $stmnt->bindParam(1,$startdate);
                $stmnt->bindParam(2,$enddate);
               $stmnt->bindParam(3,$extension);
               $stmnt->bindParam(4,$tagname);
               $stmnt->bindParam(5,$duration);
       }
      

         $stmnt->execute();
         
         $num = $stmnt->rowCount();

        
        if ($num != 0 ){

            $collection_calls_details = array();

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
                array_push($collection_calls_details, $agent);
            }
            //http_response_code(201);
            $agent_data = array();
            array_push($agent_data,$collection_calls_details);
            array_push($agent_data,$collectionTags_array );
            echo json_encode($agent_data);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }


     }

  
      public function getCollectionCallComment($caller,$getdate,$starttimestamp) {
          //build query
          $query = "SELECT * FROM  ".$this->collectionteam_callsummary_table." WHERE Caller=? AND getDate=? AND StartTimeStamp=?";

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
               $collection_comment = array("comment" => $row['comment'], "tag" => $row['tag']);
               echo json_encode($collection_comment);

          }else{
              echo json_encode(array ("comment" => "No comment"));
          }
    }
     public function putCollectionCallComment($starttimestamp,$getdate,$caller,$comment,$commentby,$tag) {
        //build query

       $query = "UPDATE `collectionteam_callsummary` SET `comment`='$comment', `commentby`='$commentby',`tag`='$tag' WHERE `StartTimeStamp`='$starttimestamp' AND `getDate`='$getdate' AND `Caller`='$caller'";
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
  
     
    public function createCollectionAgent() {
        //create query

        $query = " INSERT INTO " . $this->collectionteam . " SET  extension = :extension, name = :name, email = :email";

        // prepare queery
        $stmnt = $this->conn->prepare($query);

        // sanitize
        $this->extension = htmlspecialchars(strip_tags($this->extension));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));

        //bind values
        $stmnt->bindParam(":extension", $this->extension);
        $stmnt->bindParam(":name", $this->name);
        $stmnt->bindParam(":email", $this->email);

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
        $calltype = "collection";
       $stmnt->bindParam(":extension", $this->extension);
       $stmnt->bindParam(":calltype", $calltype);

       if($stmnt->execute()){
         return true;
       }else{
         return false;
       }
      
  }

     public function updateCollectionAgent() {

         $query = "UPDATE `collectionteam` SET `extension`='$this->extension',`name`='$this->name',`email`='$this->email' WHERE `extension`='$this->extension'";
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
    public function deleteCollectionAgent() {
	    // sanitize
	    $this->extension=htmlspecialchars(strip_tags($this->extension));


	    //delete query
	    $query = "DELETE FROM `collectionteam` WHERE `extension`='$this->extension'";

	    // prepare query
	    $stmnt = $this->conn->prepare($query);

	    $stmnt->execute();

    	 $count = $stmnt->rowCount();
        if($count !=0){
                 //delete the agent records  if there are.
                 $this->deleteCollectionAgentRecordings($this->extension);

                 echo json_encode(array("message" => "Agent Successfully Deleted"));
        }else{
             echo json_encode(array("message" => "Agent Cannot be Deleted"));
        }
     }
     private function deleteCollectionAgentRecordings($extension){
            $query = "DELETE FROM `collectionteam_callsummary` WHERE `Caller`='$extension'";

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
