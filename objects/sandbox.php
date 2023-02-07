<?php

class Csd {

	//CSD class properties
	private $csdinbound_table = "csdinbound";
	private $inbound_callstatus_table = "inbound_callstatus";
    private $csdoutbound = "outbound";
    private $parked_calls_tb = "waiting_calls";
    private $voicemail = "voicemail";

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

  private $json_addr = "/var/www/html/sbtph_csd/json/";
  
   //create database connection  when this class instantiated
    public function __construct($db){
    	$this->conn = $db;
    }

    public function genMetrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics) {
           //option 1 = csdinbound
          //option 2 = csd outbound
          //option 3 = collection
          if($option == 'csdinbound'){

            $this->inbound_metrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics);
          }elseif($option == 'csdoutbound'){
          	 $this->outbound_metrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics);
          }

    }
    public function getVoicemails() {
          $query = "SELECT * FROM ".$this->voicemail." ORDER BY date DESC ";
          $stmnt = $this->conn->prepare($query);
          $stmnt->execute();
          $num = $stmnt->rowCount();
           if ($num != 0){
            $voicemail_array = array();
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
              $voicemail = array(
                "timestamp" => $row['timestamp'],
                "caller" => $row['caller'],
                "date" => $row['date'],
                "time" => $row['time'],
                "voicemail" => $row['voicemail']


            );
            array_push($voicemail_array, $voicemail);

          }
            echo json_encode($voicemail_array);

        }else {
            echo json_encode(array("message" => "No voicemail"));
        }
    }
    public function deleteVoicemail($timestamp) {
        $query = "DELETE FROM `voicemail` WHERE `timestamp`=?";
        $stmnt = $this->conn->prepare($query);
        $stmnt->bindParam(1, $timestamp);

        if($stmnt->execute()){
            echo json_encode(array("message" => "Voicemail was Successfully Deleted."));
        }else{
            echo json_encode(array("message" => "Voicemail Cannot be Deleted."));
        }

    }
    public function getTotalCounts($getdate) {
        //MISSCALLS
        $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE `CallStatus`!=? AND `getDate`=? ";
        $stmnt = $this->conn->prepare($query);
        $calls_status = 'ANSWER';

         $stmnt->bindParam(1, $calls_status);
         $stmnt->bindParam(2, $getdate);
         $stmnt->execute();
         $total_missed_calls = $stmnt->rowCount();

          //PARKCALLS
          $query = "SELECT * FROM ".$this->parked_calls_tb."";
          $stmnt = $this->conn->prepare($query);
          $stmnt->execute();
          $parked_calls = $stmnt->rowCount();


          //VOICEMAILS
          $query = "SELECT * FROM ".$this->voicemail." ORDER BY date DESC ";
          $stmnt = $this->conn->prepare($query);
          $stmnt->execute();
          $voicemail_counts = $stmnt->rowCount();

          echo json_encode(array("total_missed_calls" => $total_missed_calls, "parked_calls" => $parked_calls, "voicemail_counts" => $voicemail_counts));

    }
    public function getParkedCalls(){
          // build query
        $query = "SELECT * FROM ".$this->parked_calls_tb."";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        //execute

        $stmnt->execute();
        $num = $stmnt->rowCount();
        if ($num != 0){
            $total_parked_calls = array();
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $startime = explode("-", $row['waitingStarTime']);

                 $datetimeFormat = "Ymd-His";
                 $date = new DateTime($startime[0] . " ". $startime[1]);
                 $startime = $date->format('Y-m-d H:i:s');

                $getcurrent_date = new DateTime();
              //  $jp_time = new DateTimeZone('Asia/Tokyo');
                //$getcurrent_date->setTimezone($jp_time);
                $getcurrent_date = $getcurrent_date->format('Y-m-d H:i:s');


                $waiting_time = strtotime($getcurrent_date) - strtotime($startime);
                 $waiting_time = $this->secToHR($waiting_time);

                 $parked_calls = array(
                "caller" => $row['caller'],
                "time" => $waiting_time,
                "getdate" => $row['getDate']


            );
            array_push($total_parked_calls, $parked_calls);

          }
            echo json_encode($total_parked_calls);

        }else {
            echo json_encode(array("message" => "NO PARK CALLS"));
        }

    }
    public function getAll() {
        // build query
        $query = "SELECT * FROM ".$this->csdinbound_table."";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        //execute
        $stmnt->execute();
        return $stmnt;
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
     public function getComment($extension,$getdate,$startimestamp) {
        //build query
        $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE WhoAnsweredCall=? AND getDate=? AND StartTimeStamp=?";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values
        $stmnt->bindParam(1,$extension);
        $stmnt->bindParam(2,$getdate);
        $stmnt->bindParam(3,$startimestamp);

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
     public function getOutboundComment($caller,$getdate,$startimestamp) {
        //build query
        $query = "SELECT * FROM  ".$this->csdoutbound." WHERE Caller=? AND getDate=? AND StartTimeStamp=?";

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
             $outbound_comment = array("comment" => $row['comment'],"tag" => $row['tag']);
             echo json_encode($outbound_comment);

        }else{
            echo json_encode(array ("comment" => "No comment"));
        }
    }

	public function changeExten($exten){
		$query = "`csdinbound` SET `extension`='9000' WHERE `username`='ROG'";

	}
    public function getActiveChannels($extension){
        $query = "SELECT * FROM `sip_channels` WHERE `extension`=?";
        $stmnt = $this->conn->prepare($query);
        $stmnt->bindParam(1,$extension);
        $stmnt->execute();
        return $stmnt->fetch();

    }
    public function active_inactive($receive_calls){
    	// build query
    	$query = "SELECT * FROM ".$this->csdinbound_table." WHERE receive_calls=?";

    	//prepare the query

    	$stmnt = $this->conn->prepare($query);

    	//bind values
    	$this->receive_calls = $receive_calls;
    	$stmnt->bindParam(1,$this->receive_calls);

    	//execute
    	$stmnt->execute();
    	return $stmnt;
    }
    public function loginLogoutDetails(){
    		$query = "SELECT * FROM  ".$this->logs_table." WHERE extension=?  ORDER BY timestamp DESC";

    		//prepare query
    		$stmnt = $this->conn->prepare($query);

    		//bind values
    		$stmnt->bindParam(1, $this->extension);

    		$stmnt->execute();
    		return $stmnt;
    }

    public function login_logout_duration ($log,$extension){
        $query = "SELECT * FROM ".$this->logs_table." WHERE log=? AND extension=? ORDER by timestamp DESC LIMIT 1;"; // the question mark(?) is a place holder

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values from question mark (?) place holder.
        $stmnt->bindParam(1,$log);
        $stmnt->bindParam(2,$extension);

        $stmnt->execute();

        $row = $stmnt->fetch(PDO::FETCH_ASSOC);
         $currenttimestamp = time();

         $duration =  ($currenttimestamp - strtotime($row['timestamp']));

         //make H:m:s time format
         $duration = $this->secToHR($duration);

         return $duration;
    }

     public function inbound_metrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics){
         // echo $option_metrics;
            $data = array();
          if ($option_metrics == 'tag'){    
             $getInboundRecords = $this->getInboundRecordsBaseOnDateRange($option,$startDateAndTime,$endDateAndTime);

             //get the range of month first 
              $array_month = array();
             while ($row_month = $getInboundRecords->fetch(PDO::FETCH_ASSOC)) {
                date("F",strtotime($row_month['getDate']));
                $getmonth = date("F",strtotime($row_month['getDate']));
                $getyear = date("Y",strtotime($row_month['getDate']));
                $month_year = $getmonth. "-" . $getyear;
                if(!array_key_exists($month_year,$array_month)){
                   $array_month[$month_year] = 0;
                }
             }
             $getInboundRecords = $this->getInboundRecordsBaseOnDateRange($option,$startDateAndTime,$endDateAndTime);
             while($row = $getInboundRecords->fetch(PDO::FETCH_ASSOC)){
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
             //add the addtional tags that has no value
             // $tags_options = array(  
             //                 'NO TAG','DROPPED CALL','SALES ASSISTANCE','CAR INQUIRY (REGISTERED)','CAR INQUIRY (UNREGISTERED)',
             //                 'PAYMENT UPDATE','PAYMENT PROBLEM','INVOICE','CONSIGNEE','COURIER','SHIPMENT','DOCUMENTS',
             //                 'RELEASE','AMENDMENT','COMPLAINTS');
               

              $getInboundTags = $this->getTags('CSDINBOUND');
      
          $tags_options = array();

          while ($row_tag = $getInboundTags->fetch(PDO::FETCH_ASSOC) ) {
             array_push($tags_options, $row_tag['tagname']);
          }               

                             
             for($t=0;$t<count($tags_options);$t++){
              if(!array_key_exists($tags_options[$t], $data)){
                $data[$tags_options[$t]] = $array_month;
              }
             }
             $data['option_metrics'] = 'tag';
              $data['option'] = $option;
              $data['dateRange'] = $origstarttime . ' To ' . $orgendtime;
            echo json_encode($data);
          }else {
                 //build query
              $query = "SELECT * FROM ".$this->csdinbound_table."  ";

              //prepare the query
              $stmnt = $this->conn->prepare($query);
              if($stmnt->execute()){
                  $calls_summary = array();
                  $grand_total_duration = 0;
                  $grand_total_counts = 0;
                  while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                        $totalAgentTimeStamp = $this->getMetricsTotalAgentTimeStamp($option,$startDateAndTime,$endDateAndTime,$row['extension']);

                        //total answer calls of each agent
                        $totalanswered = $totalAgentTimeStamp->rowCount();

                        /*This section calculate the total call duration of each agents..
                          Duration field is newly added  on the table.
                          On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                        */
                         $total_sec=0;
                         while($row_calls = $totalAgentTimeStamp->fetch(PDO::FETCH_ASSOC)) {

                           if($row_calls['Duration'] !=0){
                              $total_sec = $total_sec +  $row_calls['Duration'];
                           }else{
                             $endtime = explode("-", $row_calls['EndTimeStamp']);
                             $startime = explode("-", $row_calls['StartTimeStamp']);
                             $total_sec = $total_sec + ( (strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );
                           }
                        }
                       //make H:m:s time format
                         $grand_total_duration_sec = $grand_total_duration_sec + $total_sec;
                         $total_duration = $this->secToHR($total_sec);
                         $grand_total_counts = $grand_total_counts + $totalanswered;

                        $calls_agent_summary = array(
                          "extension" => $row['extension'],
                          "name" => $row['username'],
                          "total_answered" => $totalanswered,
                          "total_duration" => $total_duration,
                          "total_sec" => $total_sec
                        );
                    array_push($calls_summary, $calls_agent_summary);
                }
                 $grand_total_duration = $this->secToHR($grand_total_duration_sec);
                 $grand_total = array('option' => $option,'duration_weight' =>$duration_weight,'callcount_weight' =>$callcount_weight,'grand_total_duration_sec' => $grand_total_duration_sec,'grand_total_duration' => $grand_total_duration, 'grand_total_counts' => $grand_total_counts,'datetimeRange' => $origstarttime . ' To ' . $orgendtime);
                 $final_results = array();
                 array_push($final_results, $grand_total);
                 array_push($final_results, $calls_summary);

                echo json_encode($final_results);
              }else {
                echo json_encode(array ("message" => "No Records Found"));
              }
           }   
    }

    public function getMetricsTotalAgentTimeStamp($option,$startDateAndTime, $endDateAndTime, $extension){

      $query = "SELECT * FROM `inbound_callstatus` WHERE `CallStatus`=? AND`WhoAnsweredCall`=? AND `StartTimeStamp` BETWEEN ? AND ? ORDER BY `StartTimeStamp` DESC";

      $stmnt = $this->conn->prepare($query);
      $callstatus = 'ANSWER';

      //bind values
      $stmnt->bindParam(1,$callstatus);
      $stmnt->bindParam(2,$extension);
      $stmnt->bindParam(3,$startDateAndTime);
      $stmnt->bindParam(4,$endDateAndTime);

      //execute
      $stmnt->execute();
      return $stmnt;

    }

      public function getInboundRecordsBaseOnDateRange($option,$startDateAndTime, $endDateAndTime){

      $query = "SELECT * FROM `inbound_callstatus` WHERE `CallStatus`=?  AND `StartTimeStamp` BETWEEN ? AND ? ORDER BY `StartTimeStamp` ASC";

      $stmnt = $this->conn->prepare($query);
      $callstatus = 'ANSWER';

      //bind values
      $stmnt->bindParam(1,$callstatus);
      $stmnt->bindParam(2,$startDateAndTime);
      $stmnt->bindParam(3,$endDateAndTime);

      //execute
      $stmnt->execute();
      return $stmnt;

    }
   


     public function outbound_metrics($option,$startDateAndTime,$endDateAndTime,$origstarttime,$orgendtime,$duration_weight,$callcount_weight,$option_metrics){
        $data = array();
        if($option_metrics == 'tag'){
              //This code section of code is for tag metrics
               $getOutboundRecords = $this->getCsdOutboundSummaryForTaging($startDateAndTime,$endDateAndTime);

               //get the range of month first 
              $array_month = array();
           

             for($i=0 ; $i<count($getOutboundRecords[0]);$i++){
                $getmonth = date("F",strtotime($getOutboundRecords[0][$i]['getDate']));
                $getyear = date("Y",strtotime($getOutboundRecords[0][$i]['getDate']));
                $month_year = $getmonth. "-" . $getyear;
              
                if(!array_key_exists($month_year,$array_month)){
                   $array_month[$month_year] = 0;
                }
             }


        
             for($i=0; $i<count($getOutboundRecords[0]);$i++){
                $getmonth = date("F",strtotime($getOutboundRecords[0][$i]['getDate']));
                $getyear = date("Y",strtotime($getOutboundRecords[0][$i]['getDate']));
                $month_year = $getmonth. "-" . $getyear;
                if($getOutboundRecords[0][$i]['tag'] == ''){
                  $tag = 'NO TAG';
                }else{
                  $tag = $getOutboundRecords[0][$i]['tag'];
                }
                   if(array_key_exists($tag, $data)){
                  $data[$tag][$month_year] = $data[$tag][$month_year] + 1;
                }else{
                  $data[$tag] = $array_month;
                  $data[$tag][$month_year] = 1;
                }
             }
             //add the addtional tags that has no value
             // $tags_options = array('NO TAG','MISSED CALL RETURN CALL','DROPPED CALL RETURN CALL','CONSIGNEE CALL',
             //                       'COURIER CALL','FOLLOW UP CALL INQUIRY','FOLLOW UP CALL PAYMENT','FOLLOW UP CALL SHIPMENT',
             //                       'FOLLOW UP CALL COMPLAINT'
             //                      );

              $getOutboundTags = $this->getTags('CSDOUTBOUND');
      
          $tags_options = array();

          while ($row_tag = $getOutboundTags->fetch(PDO::FETCH_ASSOC) ) {
             array_push($tags_options, $row_tag['tagname']);
          }
                             
             for($t=0;$t<count($tags_options);$t++){
              if(!array_key_exists($tags_options[$t], $data)){
                $data[$tags_options[$t]] = $array_month;
              }
             } 
             $data['option_metrics'] = 'tag';
              $data['option'] = $option;
              $data['dateRange'] = $origstarttime . ' To ' . $orgendtime;
              $data['total_records'] = $getOutboundRecords[1]['total_count'];
            echo json_encode($data);

              //end section of  tag metrics code

          

        }else{
              // build the query
            $query = "SELECT * FROM ".$this->csdinbound_table."  ";

            //prepare the query
            $stmnt = $this->conn->prepare($query);

            if($stmnt->execute()){
                $outbound_summary = array();
                while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                    $getAgentTotalRecords = $this->getoutboundAgentTotalRecords($startDateAndTime,$endDateAndTime,$row['extension']);

                     //total answer calls of each agent
                    $totalMadeCalls = $getAgentTotalRecords->rowCount();

                    /*This section calculate the total call duration of each agents..
                      Duration field is newly added  on the table.
                      On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                    */
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

                     $outbound_agent_summary = array(
                    "extension" => $row['extension'],
                    "name" => $row['username'],
                    "total_answered" => $totalMadeCalls,
                    "total_duration" => $total_duration,
                    "total_sec" => $total_sec
                     );

                     array_push($outbound_summary, $outbound_agent_summary);
                 }
                  $grand_total_duration = $this->secToHR($grand_total_duration_sec);
                  $grand_total = array('option' => $option, 'duration_weight' => $duration_weight,'callcount_weight' =>$callcount_weight,'grand_total_duration_sec' => $grand_total_duration_sec,'grand_total_duration' => $grand_total_duration, 'grand_total_counts' => $grand_total_counts,'datetimeRange' => $origstarttime . ' To ' . $orgendtime);
                  $final_results = array();
                  array_push($final_results, $grand_total);
                  array_push($final_results, $outbound_summary);

                  echo json_encode($final_results);

              }
         }
        
    }
    
    //convert to outbound tags metrcis
     public function getCsdOutboundSummaryForTaging($startDateAndTime, $endDateAndTime){
        
        // build the query
        $query = "SELECT * FROM ".$this->csdinbound_table."  ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        if($stmnt->execute()){
            $data = array();
            $outbound_details = array();
            $total_count = 0;
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $getAgentTotalRecords = $this->getoutboundAgentTotalRecords($startDateAndTime, $endDateAndTime,$row['extension']);

                 
                 while($row_calls = $getAgentTotalRecords->fetch(PDO::FETCH_ASSOC)) {
                      $total_count++;
                      $agent =  array(
                        "StartTimeStamp" => $row_calls['StartTimeStamp'],
                        "EndTimeStamp" => $row_calls['EndTimeStamp'],
                        "CallStatus" => $row_calls['CallStatus'],
                        "Caller" => $row_calls['Caller'],
                        "CalledNumber" => $row_calls['CalledNumber'],
                        "getDate" => $row_calls['getDate'],
                        "recoring_link" => $row_calls['recoring_link'],
                        "comment" => $row_calls['comment'],
                        "tag" => $row_calls['tag']

                      );
                      array_push($outbound_details, $agent);
                    }
                  
                 
             }
             $total = array(
              "total_count" => $total_count
             );
             array_push($data, $outbound_details);
             array_push($data,$total);
             return $data;
        }

    } 
     public function getoutboundAgentTotalRecords($startDateAndTime, $endDateAndTime, $extension) {

        //build query
        $query  = "SELECT * FROM ".$this->csdoutbound." WHERE  CallStatus='ANSWER'  AND Caller =? AND StartTimeStamp BETWEEN ? AND ? ORDER BY StartTimeStamp DESC ";

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

    

    public function call_summary($getdate){
         $currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }
    	//build query
    	$query = "SELECT * FROM ".$this->csdinbound_table."  ";

    	//prepare the query

    	$stmnt = $this->conn->prepare($query);

    	if($stmnt->execute()){
            $calls_summary = array();
    				while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $totalAgentTimeStamp = $this->getTotalAgentTimeStamp($getdate,$row['extension']);

                //total answer calls of each agent
                $totalanswered = $totalAgentTimeStamp->rowCount();

								/*This section calculate the total call duration of each agents..
									Duration field is newly added  on the table.
									On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
								*/
                 $total=0;
                 while($row_calls = $totalAgentTimeStamp->fetch(PDO::FETCH_ASSOC)) {
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

		            $calls_agent_summary = array(
		            	"extension" => $row['extension'],
		            	"name" => $row['username'],
		            	"total_answered" => $totalanswered,
		            	"total_duration" => $total_duration,
		            	"getdate" => $getdate,
		               "call_details" => "agent_call_details.php?extension=" .$row['extension'] . "&username=" .$row['username'] . "&getdate=" .$getdate

		            );
            	array_push($calls_summary, $calls_agent_summary);
    		}

    		echo json_encode($calls_summary);
    	}else {
    		echo json_encode(array ("message" => "No Records Found"));
    	}

    }

    public function call_summary_export($getdate){
        $currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }

        // build the query
       $query = "SELECT * FROM ".$this->csdinbound_table."  ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        if($stmnt->execute()){

             $inbound_summary_template_json = file_get_contents($this->json_addr."inbound_summary.json");
            //make an object
            $inbound_summary_call_details_obj = json_decode($inbound_summary_template_json, FALSE);
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $totalAgentTimeStamp = $this->getTotalAgentTimeStamp($getdate,$row['extension']);

                //total answer calls of each agent
                $totalanswered = $totalAgentTimeStamp->rowCount();

								/*This section calculate the total call duration of each agents..
									Duration field is newly added  on the table.
									On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
								*/
								 $total=0;
								 while($row_calls = $totalAgentTimeStamp->fetch(PDO::FETCH_ASSOC)) {
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

                 $inbound_summary = array();
                 //put each field to each array
                 $array_extension = array("text" => $row['extension']);
                 $array_name = array("text" => $row['username']);
                 $array_total_answered = array("text" =>  $totalanswered);
                 $array_total_duration = array("text" => $total_duration);
                 $array_getdate = array("text" => $getdate);

                 //push it one by one
                 array_push($inbound_summary,$array_extension);
                 array_push($inbound_summary, $array_name);
                 array_push($inbound_summary, $array_total_answered);
                 array_push($inbound_summary, $array_total_duration);
                 array_push($inbound_summary, $array_getdate);

                 array_push($inbound_summary_call_details_obj->tableData[0]->data, $inbound_summary);

             }
            //echo as json
            echo json_encode($inbound_summary_call_details_obj);
        }
    }

    public function agentCallDetails($extension,$username,$getdate){

      $getInboundTags = $this->getTags('CSDINBOUND');
      
      $inboundTags_array = array();

      while ($row_tag = $getInboundTags->fetch(PDO::FETCH_ASSOC) ) {
         array_push($inboundTags_array, $row_tag['tagname']);
      }



    	//build query
    	$query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE WhoAnsweredCall=? AND getDate=?";

    	//prepare the query
    	$stmnt = $this->conn->prepare($query);

    	//bind values
    	$stmnt->bindParam(1,$extension);
    	$stmnt->bindParam(2,$getdate);

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
                    "startimestamp" => $row['StartTimeStamp'],
                    "tag" => $row['tag']
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
                    "startimestamp" => $row['StartTimeStamp'],
                    "tag" => $row['tag']
                );
                array_push($search_details,$agent);
            }
            //http_response_code(201);
            echo json_encode($search_details);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
    }
    public function csdinboundCallDetailsExport($extension,$username,$getdate){
      //  build query
        $query = "SELECT * FROM  ".$this->inbound_callstatus_table." WHERE WhoAnsweredCall=? AND getDate=?";

         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $extension);
         $stmnt->bindParam(2, $getdate);

         $stmnt->execute();

         $num = $stmnt->rowCount();

        if ($num != 0 ){

            $inbound_call_details_template_json = file_get_contents($this->json_addr."inbound_call_details.json");
            //make an object
            $inbound_call_details_obj = json_decode($inbound_call_details_template_json, FALSE);

            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
							$total=0;
							//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
							 if($row['Duration'] != 0){
								 $total = $total + $row['Duration'];
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $EndTime = strtotime($EndTime);
								 $StartTime =  $EndTime - $duration;

							 }
							 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
							 else{
								 $endtime = explode("-", $row['EndTimeStamp']);
								 $startime = explode("-", $row['StartTimeStamp']);
								 $total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

								 $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $StartTime = strtotime($StartTime);
								 $EndTime = strtotime($EndTime);
							 }
								$duration = $this->secToHR($total);

                //get recordings url
                $base_url = "http://211.0.128.110/callrecording/incoming/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;

                 $agent = array();
                //put each field to each array
                $array_username = array("text" => $username);
                $array_extension = array("text" => $extension);
                $array_calledNumber = array("text" => $row['CalledNumber']);
                $array_caller = array("text" => $row['Caller'] );
                $array_callStatus = array("text" => $row['CallStatus']);
                $array_startime = array("text" => date( "h:i:s a",$StartTime));
                $array_endtime = array("text" => date("h:i:s a",$EndTime));
                $array_callDuration = array("text" => $duration );
                $array_callrecording = array("text" => $full_url);
                $array_getDate = array("text" => $row['getDate']);
                $array_comment = array("text" =>  $row['comment']);
                $array_tag = array("text" => $row['tag']);

                //push it
                array_push($agent,$array_username);
                array_push($agent, $array_extension);
                array_push($agent,$array_calledNumber);
                array_push($agent,$array_caller);
                array_push($agent, $array_callStatus);
                array_push($agent,$array_startime);
                array_push($agent, $array_endtime);
                array_push($agent, $array_callDuration);
                array_push($agent,$array_callrecording);
                array_push($agent, $array_getDate);
                array_push($agent,$array_comment);
                array_push($agent, $array_tag);


                array_push($inbound_call_details_obj->tableData[0]->data, $agent);
            }
            //http_response_code(201);

            echo json_encode($inbound_call_details_obj);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
     }

    public function csdMissedCalls($getdate,$option) {
        //build query
        $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE `CallStatus`!=? AND `getDate`=? ";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        $calls_status = 'ANSWER';
       // $caller = '';
        //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $calls_status);
         $stmnt->bindParam(2, $getdate);
         //$stmnt->bindParam(3,$caller);

        $stmnt->execute();

         $num = $stmnt->rowCount();
         $missedcalls_array = array();
         if($num != 0){
            if($option =='summary') {
                        $missedcall = array(
                            "total_missed_calls" => $num,
                            "getdate" => $getdate,
                            "misscalls_details" => "csd_missed_calls_details.php?getdate=" .$getdate
                        );
              array_push($missedcalls_array,$missedcall);
              }elseif ($option == 'details'){

                while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                   if ($row['StartTimeStamp'] == 'NONE'){
                    $StartTime = '';
                   }else{
                    $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
                    $StartTime = strtotime($StartTime);
                    $StartTime = date( "h:i:s a",$StartTime);
                   }if($row['EndTimeStamp'] == 'NONE'){
                    $EndTime = '';
                   }else{
                     $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
                     $EndTime = strtotime($EndTime);
                     $EndTime = date("h:i:s a",$EndTime);
                   }


                    $missedcall = array(
                             "startime" =>  $StartTime,
                             "endtime" =>  $EndTime,
                             "caller" => $row['Caller'],
                             "callStatus" => $row['CallStatus'],
                             "getdate" => $getdate
                       );

                  array_push($missedcalls_array,$missedcall);
                }
             }
             echo json_encode($missedcalls_array);
            }else{
            echo json_encode(array("message" => "No Records Found"));
         }

    }

     public function putComment($startimestamp, $getdate, $whoansweredcall, $comment,$tag) {
      
       $query = "UPDATE `inbound_callstatus` SET `comment`='$comment',`tag`='$tag' WHERE `StartTimeStamp`='$startimestamp' AND `getDate`='$getdate' AND `WhoAnsweredCall`='$whoansweredcall'";
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
     public function putoutboundComment($startimestamp, $getdate, $caller, $comment,$tag) {
        //build query
       $query = "UPDATE `outbound` SET `comment`='$comment',`tag`='$tag' WHERE `StartTimeStamp`='$startimestamp' AND `getDate`='$getdate' AND `Caller`='$caller'";
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

    public function updateCSDAgent($extension,$name,$email) {

      // $query = "UPDATE `csdinbound` SET `comment`='$comment' WHERE `StartTimeStamp`='$startimestamp' AND `getDate`='$getdate' AND `WhoAnsweredCall`='$whoansweredcall'";
       $query = "UPDATE `csdinbound` SET `extension`='$extension',`username`='$name',`email`='$email' WHERE `extension`='$extension'";
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

    public function getTotalAgentTimeStamp($getdate, $extension){

    	$query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE getDate=? AND CallStatus='ANSWER'  AND WhoAnsweredCall=?";

    	$stmnt = $this->conn->prepare($query);

    	//bind values
    	$stmnt->bindParam(1,$getdate);
    	$stmnt->bindParam(2,$extension);

    	//execute
    	$stmnt->execute();
    	return $stmnt;

    }

    public function getCsdOutboundSummary($getdate){
        $currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }

        // build the query
        $query = "SELECT * FROM ".$this->csdinbound_table."  ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        if($stmnt->execute()){
            $outbound_summary = array();
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $getAgentTotalRecords = $this->getAgentTotalRecords($row['extension'],$getdate);

                 //total answer calls of each agent
                $totalMadeCalls = $getAgentTotalRecords->rowCount();
                //This section calculate the total call duration of each agents..
                 $total=0;
                 while($row_calls = $getAgentTotalRecords->fetch(PDO::FETCH_ASSOC)) {
										 if($row_calls['Duration'] != 0){
											 $total = $total + $row_calls['Duration'];
										 }else{
											 $endtime = explode("-", $row_calls['EndTimeStamp']);
	                     $startime = explode("-", $row_calls['StartTimeStamp']);
	                     $total = $total + ( (strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );
	                  }
									}

                // make H:m:s time format
                 $total_duration = $this->secToHR($total);

                 $outbound_agent_summary = array(
                    "extension" => $row['extension'],
                    "username" => $row['username'],
                    "totalmadecalls" => $totalMadeCalls,
                    "totalduration" => $total_duration,
                    "getdate" => $getdate,
                    "calldetails" => "outbound_agent_call_details.php?extension=" . $row['extension'] . "&username=" . $row['username'] . "&getdate=" . $getdate

                 );

                 array_push($outbound_summary, $outbound_agent_summary);
             }
            echo json_encode($outbound_summary);
        }

    }

     public function getCsdOutboundSummary_Export($getdate){
        $currentdate = date('Y-m-d');

        if(strtotime($getdate) > strtotime($currentdate)){
            echo json_encode(array ("message" => "No Records Found"));
            exit();
        }

        // build the query
        $query = "SELECT * FROM ".$this->csdinbound_table."  ";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        if($stmnt->execute()){

             $outbound_summary_template_json = file_get_contents($this->json_addr."outbound_summary.json");
            //make an object
            $outbound_summary_call_details_obj = json_decode($outbound_summary_template_json, FALSE);
            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
                $getAgentTotalRecords = $this->getAgentTotalRecords($row['extension'],$getdate);

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

                 $outbound_summary = array();
                 //put each field to each array
                 $array_extension = array("text" => $row['extension']);
                 $array_name = array("text" => $row['name']);
                 $array_totalmadecalls = array("text" =>  $totalMadeCalls);
                 $array_totalduration = array("text" => $total_duration);
                 $array_getdate = array("text" => $getdate);

                 //push it one by one
                 array_push($outbound_summary,$array_extension);
                 array_push($outbound_summary, $array_name);
                 array_push($outbound_summary, $array_totalmadecalls);
                 array_push($outbound_summary, $array_totalduration);
                 array_push($outbound_summary, $array_getdate);

                 array_push($outbound_summary_call_details_obj->tableData[0]->data, $outbound_summary);
             }
            //echo as json
            echo json_encode($outbound_summary_call_details_obj);

        }
    }
     public function searchCalledNumberCallDetails($callednumber){
         //  build query
        $query = "SELECT * FROM " . $this->csdoutbound . " WHERE CalledNumber=? ORDER BY getDate DESC";
        // $query  = "SELECT StartTimeStamp,EndTimeStamp FROM ".$this->collectionteam_callsummary." WHERE getDate='2019-09-13' AND CallStatus='ANSWER'  AND Caller ='6340'";
        // //prepare the query
         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $callednumber);


         $stmnt->execute();

         $num = $stmnt->rowCount();


        if ($num != 0 ){

            $csdoutbound_calls_details = array();

            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
							$total=0;
							//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
							 if($row['Duration'] != 0 ){
								 $total = $total + $row['Duration'];
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $EndTime = strtotime($EndTime);
								 $StartTime =  $EndTime - $duration;

							 }
							 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
							 else{
								 $endtime = explode("-", $row['EndTimeStamp']);
								 $startime = explode("-", $row['StartTimeStamp']);
								 $total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

								 $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $StartTime = strtotime($StartTime);
								 $EndTime = strtotime($EndTime);
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
                    $agent_name = $agent_row['username'];
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
                array_push($csdoutbound_calls_details, $agent);
            }
            //http_response_code(201);
            echo json_encode($csdoutbound_calls_details);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
     }

     public function csdOutboundCallDetails($extension,$username,$getdate){

      $getOutboundTags = $this->getTags('CSDOUTBOUND');
      
      $outboundTags_array = array();

      while ($row_tag = $getOutboundTags->fetch(PDO::FETCH_ASSOC) ) {
         array_push($outboundTags_array, $row_tag['tagname']);
      }
         //  build query
        $query = "SELECT * FROM " . $this->csdoutbound . " WHERE Caller=? AND CallStatus='ANSWER' AND getDate=?";
        // $query  = "SELECT StartTimeStamp,EndTimeStamp FROM ".$this->collectionteam_callsummary." WHERE getDate='2019-09-13' AND CallStatus='ANSWER'  AND Caller ='6340'";
        // //prepare the query
         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $extension);
         $stmnt->bindParam(2, $getdate);

         $stmnt->execute();

         $num = $stmnt->rowCount();


        if ($num != 0 ){

            $csdoutbound_calls_details = array();

            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
							$total=0;
							//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
							 if($row['Duration'] != 0){
								 $total = $total + $row['Duration'];
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $EndTime = strtotime($EndTime);
								 $StartTime =  $EndTime - $duration;

							 }
							 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
							 else{
									$endtime = explode("-", $row['EndTimeStamp']);
	 								$startime = explode("-", $row['StartTimeStamp']);
	 								$total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

	 								$StartTime = str_replace("-", " ", $row['StartTimeStamp']);
	 								$EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
	 								$StartTime = strtotime($StartTime);
	 								$EndTime = strtotime($EndTime);
							 }
								$duration = $this->secToHR($total);

                //get recordings url
                $base_url = "http://211.0.128.110/callrecording/outgoing/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;

                 $agent = array(
                    "username" => $username,
                    "caller" => $extension,
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
                array_push($csdoutbound_calls_details, $agent);
            }
            $agent_data = array();
            array_push($agent_data,$csdoutbound_calls_details);
            array_push($agent_data,$outboundTags_array);
            //http_response_code(201);
            echo json_encode($agent_data);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
     }

     public function csdOutboundCallDetailsExport($extension,$username,$getdate){

      //  build query
         $query = "SELECT * FROM " . $this->csdoutbound . " WHERE Caller=? AND CallStatus='ANSWER' AND getDate=?";

         $stmnt = $this->conn->prepare($query);

         //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $extension);
         $stmnt->bindParam(2, $getdate);

         $stmnt->execute();

         $num = $stmnt->rowCount();


        if ($num != 0 ){

            $outbound_call_details_template_json = file_get_contents($this->json_addr."outbound_call_details.json");
            //make an object
            $outbound_call_details_obj = json_decode($outbound_call_details_template_json, FALSE);

            while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
							$total=0;
							//Duration Field is new added to the table and the old records has empty duration field so need to used start and end timestamp to compute the duration
							 if($row['Duration'] != 0){
								 $total = $total + $row['Duration'];
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $EndTime = strtotime($EndTime);
								 $StartTime =  $EndTime - $duration;

							 }
							 // this is where the duration is available so no need to compute the duration but need to compute the start timestamp.
							 else{
								 $endtime = explode("-", $row['EndTimeStamp']);
								 $startime = explode("-", $row['StartTimeStamp']);
								 $total = $total + ((strtotime($endtime[0]) + strtotime($endtime[1])) - (strtotime($startime[0]) +strtotime($startime[1])) );

								 $StartTime = str_replace("-", " ", $row['StartTimeStamp']);
								 $EndTime  = str_replace("-", " ", $row['EndTimeStamp']);
								 $StartTime = strtotime($StartTime);
								 $EndTime = strtotime($EndTime);
							 }
								$duration = $this->secToHR($total);

                //get recordings url
                $base_url = "http://211.0.128.110/callrecording/outgoing/";
                $date_folder = str_replace('-',"", $row['getDate']);
                $filename = $row['Caller'] .'-'. $row['CalledNumber'] .'-' .$row['StartTimeStamp']. ".mp3";
                $full_url = $base_url . $date_folder .'/'.$filename;

                 $agent = array();
                //put each field to each array
                $array_username = array("text" => $username);
                $array_extension = array("text" => $extension);
                $array_calledNumber = array("text" => $row['CalledNumber']);
                $array_caller = array("text" => $row['Caller'] );
                $array_callStatus = array("text" => $row['CallStatus']);
                $array_startime = array("text" => date( "h:i:s a",$StartTime));
                $array_endtime = array("text" => date("h:i:s a",$EndTime));
                $array_callDuration = array("text" => $duration );
                $array_callrecording = array("text" => $full_url);
                $array_getDate = array("text" => $row['getDate']);
                $array_comment = array("text" =>  $row['comment']);
                $array_tag = array("text" => $row['tag']);

                //push it
                array_push($agent,$array_username);
                array_push($agent, $array_extension);
                array_push($agent,$array_calledNumber);
                array_push($agent,$array_caller);
                array_push($agent, $array_callStatus);
                array_push($agent,$array_startime);
                array_push($agent, $array_endtime);
                array_push($agent, $array_callDuration);
                array_push($agent,$array_callrecording);
                array_push($agent, $array_getDate);
                array_push($agent,$array_comment);
                array_push($agent,$array_tag);
                array_push($outbound_call_details_obj->tableData[0]->data, $agent);
            }
            //http_response_code(201);

            echo json_encode($outbound_call_details_obj);
        }else{
            echo json_encode(array ("message" => "No Records Found"));
        }
     }
    public function getAgentTotalRecords($extension, $getdate) {
        //build query
        $query  = "SELECT * FROM ".$this->csdoutbound." WHERE getDate=? AND CallStatus='ANSWER'  AND Caller =?";

        //prepare the query
        $stmnt = $this->conn->prepare($query);

        //bind values from question mark (?) place holder.
         $stmnt->bindParam(1,$getdate);
         $stmnt->bindParam(2,$extension);

        //execute
       $stmnt->execute();

       return $stmnt;
    }
    //added-08222020
    public function createTag() {

            //create query

              $query = " INSERT INTO " . $this->tag . " SET  tagId = :tagId, tagtype = :tagtype, tagname = :tagname, createdby = :createdby, createddate = :createddate";
              // prepare queery
              $stmnt = $this->conn->prepare($query);

              // sanitize
             
              $this->tagtype = htmlspecialchars(strip_tags($this->tagtype));
              $this->tagname = htmlspecialchars(strip_tags($this->tagname));
              $this->createdby = htmlspecialchars(strip_tags($this->createdby));
              $this->createddate = htmlspecialchars(strip_tags($this->createddate));

               $tagId = $this->tagtype. "-".$this->tagname ;

              //bind values
              $stmnt->bindParam(":tagId", $tagId);
              $stmnt->bindParam(":tagtype", $this->tagtype);
              $stmnt->bindParam(":tagname", $this->tagname);
              $stmnt->bindParam(":createdby", $this->createdby);
              $stmnt->bindParam(":createddate", $this->createddate);

              //execute query
              if($stmnt->execute()){
                  return true;
              }
              return false;   
    }

    public function getAllTag() {
        // build query
        $query = "SELECT * FROM ".$this->tag."";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        //execute
        $stmnt->execute();
        return $stmnt;
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

    public function deleteTag() {
    // sanitize
    $this->tagId=htmlspecialchars(strip_tags($this->tagId));
    //delete query
    $query = "DELETE FROM `tag` WHERE `tagId`='$this->tagId'";

    // prepare query
    $stmnt = $this->conn->prepare($query);

    $stmnt->execute();

     $count = $stmnt->rowCount();
        if($count !=0){
                 
                
                 echo json_encode(array("message" => "Tag Successfully Deleted"));
        }else{
             echo json_encode(array("message" => "Tag Cannot be Deleted"));
        }
     }


     
     public function createAgent() {
        //create query

        $query = " INSERT INTO " . $this->csdinbound_table . " SET  extension = :extension, username = :name, email = :email";
        // prepare queery
        $stmnt = $this->conn->prepare($query);

        // sanitize
        $this->extension = htmlspecialchars(strip_tags($this->extension));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));

        //bind values
        $stmnt->bindParam(":extension", $this->extension);
        $stmnt->bindParam(":name", $this->username);
        $stmnt->bindParam(":email", $this->email);

        //execute query
        if($stmnt->execute()){
            return true;
        }
        return false;
    }
    public function deleteAgent() {
    // sanitize
    $this->extension=htmlspecialchars(strip_tags($this->extension));
    //delete query
    $query = "DELETE FROM `csdinbound` WHERE `extension`='$this->extension'";

    // prepare query
    $stmnt = $this->conn->prepare($query);

    $stmnt->execute();

     $count = $stmnt->rowCount();
        if($count !=0){
                 //delete the agent records  if there are.
                 $this->deleteAgentRecordings($this->extension);
                 $this->deleteAgentLogs($this->extension);
                 echo json_encode(array("message" => "Agent Successfully Deleted"));
        }else{
             echo json_encode(array("message" => "Agent Cannot be Deleted"));
        }
     }
     private function deleteAgentRecordings($extension){
            $query = "DELETE FROM `inbound_callstatus` WHERE `WhoAnsweredCall`='$extension'";

            $stmnt = $this->conn->prepare($query);

            $stmnt->execute();
            $count = $stmnt->rowCount();

     }
     private function deleteAgentLogs($extension){
        $query = "DELETE FROM `logs` WHERE `extension`='$extension'";

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
