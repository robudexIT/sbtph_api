<?php

class Csd {

	//CSD class properties
	private $csdinbound_table = "csdinbound";
	private $inbound_callstatus_table = "inbound_callstatus";
    private $csdoutbound = "outbound";
    private $parked_calls_tb = "waiting_calls";
    private $voicemail = "voicemail";
    private $calltype = "calltype";
    private $collection_table = "collectionteam";

	private $logs_table = "logs";
	private $event_log = "event_log";
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
  public $action;
  public $performed_by;
  public $description;

  // end

  private $json_addr = "/var/www/html/sbtph_csd_dev/json/";
  
   //create database connection  when this class instantiated
  public function __construct($db){
    	$this->conn = $db;
    }

  
  public function csdMissedCalls($startdate,$enddate,$option) {
        //build query
        $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE `CallStatus`!=? AND `getDate` BETWEEN ? AND ? ORDER BY getDate DESC";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        $calls_status = 'ANSWER';
       // $caller = '';
        //bind values from question mark (?) place holder
        $stmnt->bindParam(1, $calls_status);
        $stmnt->bindParam(2, $startdate);
        $stmnt->bindParam(3, $enddate);
         
         

         $stmnt->execute();

         $num = $stmnt->rowCount();
         $missedcalls_array = array();
         if($num != 0){
            if($option =='summary') {
              $getdate = '('.$startdate.')'. "-".'('.$enddate.')';
              if(strtotime($startdate) == strtotime($enddate)){
                 $getdate = $startdate;
              } 
              $missedcall = array(
                            "total_missed_calls" => $num,
                            "getdate" => $getdate,
                            "misscalls_details" => "csd_missed_calls_details.php?startdate=" .$startdate . "&enddate=".$enddate
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
                             "starttimestamp" => $row['StartTimeStamp'],
                             "extension" => $row['WhoAnsweredCall'],
                             "endtime" =>  $EndTime,
                             "caller" => $row['Caller'],
                             "callStatus" => $row['CallStatus'],
                             "comment" => $row['comment'],
                             "commentby" => $row['commentby'],
                             "getDate" => $row['getDate']
                       );

                  array_push($missedcalls_array,$missedcall);
                }
             }
             echo json_encode($missedcalls_array);
            }else{
            echo json_encode(array("message" => "No Records Found"));
         }

    }

     public function csdMissedCallsExport($startdate,$enddate) {
        //build query
        $query = "SELECT * FROM ".$this->inbound_callstatus_table." WHERE `CallStatus`!=? AND `getDate` BETWEEN ? AND ? ORDER BY getDate DESC";

        //prepare the query

        $stmnt = $this->conn->prepare($query);
        $calls_status = 'ANSWER';
       // $caller = '';
        //bind values from question mark (?) place holder
         $stmnt->bindParam(1, $calls_status);
         $stmnt->bindParam(2, $startdate);
          $stmnt->bindParam(3, $enddate);
         
         $stmnt->execute();

         $num = $stmnt->rowCount();
         
         if($num != 0){
                $missed_calls_details_template_json = file_get_contents($this->json_addr."missedcalls_details.json");
            //make an object
              $missed_calls_details_obj = json_decode($missed_calls_details_template_json, FALSE);
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

                    $missedcall = array();
                   

                    $array_startime = array("text" => $StartTime);
                    $array_endtime = array("text" => $EndTime);
                    $array_caller = array("text" => $row['Caller']);
                    $array_callStatus = array("text" => $row['CallStatus']);
                    $array_comment = array("text" => $row['comment']);
                    $array_commentby = array("text" => $row['commentby']);
                    $array_getdate = array("text" => $row['getDate']);

                     //push it
                    array_push($missedcall,$array_startime);
                    array_push($missedcall,$array_endtime);
                    array_push($missedcall, $array_caller);
                    array_push($missedcall,$array_callStatus);
                    array_push($missedcall,$array_comment);
                    array_push($missedcall,$array_commentby);
                    array_push($missedcall,$array_getdate);

                  array_push($missed_calls_details_obj->tableData[0]->data,$missedcall);
                }
                echo json_encode($missed_calls_details_obj);
               
            }else{
            echo json_encode(array("message" => "No Records Found"));
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
             $total_records = 0;
             while($row = $getInboundRecords->fetch(PDO::FETCH_ASSOC)){
               $total_records++;
                $getmonth = date("F",strtotime($row['getDate']));
                $getyear = date("Y",strtotime($row['getDate']));
                $month_year = $getmonth. "-" . $getyear;
                if($row['tag'] == ''){
                  $tag = 'NO TAG';
                }else{
                  $tag = $row['tag'];
                }
                if($row['CallStatus'] !== 'ANSWER'){
                     $tag = 'MISSEDCALLS';
                }
                if(array_key_exists($tag, $data)){
                  $data[$tag][$month_year] = $data[$tag][$month_year] + 1;
                }else{
                  $data[$tag] = $array_month;
                  $data[$tag][$month_year] = 1;
                }
                
             } 
            

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
              $data['total_records'] = $total_records;
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
                        $totalAgentTimeRecords = $this->getcsdInboundCallAgentTotalRecords($option,$startDateAndTime,$endDateAndTime,$row['extension']);

                        //total answer calls of each agent
                        $totalanswered = $totalAgentTimeRecords->rowCount();

                        /*This section calculate the total call duration of each agents..
                          Duration field is newly added  on the table.
                          On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                        */
                         $total_sec=0;
                         while($row_calls = $totalAgentTimeRecords->fetch(PDO::FETCH_ASSOC)) {


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


     public function getcsdInboundCallAgentTotalRecords($option,$startDateAndTime, $endDateAndTime, $extension){

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

      $query = "SELECT * FROM `inbound_callstatus` WHERE  `StartTimeStamp` BETWEEN ? AND ? ORDER BY `StartTimeStamp` ASC";

      $stmnt = $this->conn->prepare($query);
     

      //bind values
    
      $stmnt->bindParam(1,$startDateAndTime);
      $stmnt->bindParam(2,$endDateAndTime);

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
                    $getAgentTotalRecords = $this->getcsdOutboundCallAgentTotalRecords($startDateAndTime,$endDateAndTime,$row['extension']);

                     //total answer calls of each agent
                    $totalMadeCalls = $getAgentTotalRecords->rowCount();

                    /*This section calculate the total call duration of each agents..
                      Duration field is newly added  on the table.
                      On the  old records that Duration field is empty, Duraiton is calculated using the End and start timestamp
                    */
                     $total_sec = 0;
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
                    "total_duration" =>  $total_duration,
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
    
    //get only the records that  belong to csd 
    //because outbound table in database are shared between sales and csd who call make outbound call
    //maybe next project is to figure out how to make it seperate table
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
                $getAgentTotalRecords = $this->getcsdOutboundCallAgentTotalRecords($startDateAndTime, $endDateAndTime,$row['extension']);

                 
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
     public function getcsdOutboundCallAgentTotalRecords($startDateAndTime, $endDateAndTime, $extension) {

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
        }else{
          return false;
        }

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
        $calltype = "csd";
        $stmnt->bindParam(":extension", $this->extension);
        $stmnt->bindParam(":calltype", $calltype);

        if($stmnt->execute()){
          return true;
        }else{
          return false;
        }
       
   }

   public function updateCallType($extension,$calltype) {

    // $query = "UPDATE `csdinbound` SET `comment`='$comment' WHERE `StartTimeStamp`='$startimestamp' AND `getDate`='$getdate' AND `WhoAnsweredCall`='$whoansweredcall'";
     $query = "UPDATE `calltype` SET `extension`='$extension',`calltype`='$calltype' WHERE `extension`='$extension'";
      //prepare query
      $stmnt = $this->conn->prepare($query);
      $stmnt->execute();
      $count = $stmnt->rowCount();
      if($count !=0){
                //$this->getAllCollectionCallType();
               echo json_encode(array("message" => "Successfully Updated The Call Settings!"));
      }else{
           echo json_encode(array("message" => "Update was not Successfull"));
      }
  }

  public function getAllCollectionCallType(){
         // build the query
         $query = "SELECT extension FROM ".$this->calltype." WHERE calltype=? ";
         $calltype = "collection";
         //prepare the query
         $stmnt = $this->conn->prepare($query);
          //bind values
        $stmnt->bindParam(1,$calltype);

         if($stmnt->execute()){
             $collection_extensions = array();
             while($row = $stmnt->fetch(PDO::FETCH_ASSOC)){
               array_push($collection_extensions, $row['extension']);
            }
           
            $collection_implode =  implode("|",$collection_extensions);
            $collection_string_pattern = '/'. $collection_implode . '/';
            echo $collection_string_pattern;
            $fp = fopen('collection_extension_list.txt', 'w');
            fwrite($fp, $collection_string_pattern);
            
            fclose($fp);
            $output=null;
            $retval=null;
            exec('rsync -a -e "ssh -p 20022" collection_extension_list.txt root@61.194.115.115:/root/SCRIPTS', $output, $retval);

              if($retval != 0){
              echo "Transfer was not successfull";
              }else{
                echo json_encode(array("message" => "Successfully Updated CallType"));
            }

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
                $response->message = "Agent Info Successfully Updated";
                $response->extension = $extension;
                $response->name = $name;
                $response->email = $email;
                 echo json_encode(array($response));
        }else{
             echo json_encode(array("message" => "Update was not Successfull"));
        }
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
                //  $this->deleteAgentCallType($this->extension);
                 $response->message =  "Agent Successfully Deleted";
                 $response->extension = $this->extension;
                 echo json_encode(array($response));
        }else{
             echo json_encode(array("message" => "Agent Cannot be Deleted"));
        }
     }
     private function deleteAgentCallType($extension){
        $query = "DELETE FROM `calltype` WHERE `extension`='$extension'";

        $stmnt = $this->conn->prepare($query);

        $stmnt->execute();
        $count = $stmnt->rowCount();
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

       //added-08222020
    public function createEventLog() {

            //create query

              $query = " INSERT INTO " . $this->event_log . " SET  action = :action, performed_by = :performed_by, description = :description";
              // prepare queery
              $stmnt = $this->conn->prepare($query);

              // sanitize
             
              $this->action = htmlspecialchars(strip_tags($this->action));
              $this->performed_by = htmlspecialchars(strip_tags($this->performed_by));
              $this->description = htmlspecialchars(strip_tags($this->description));
            
              //bind values
           
              $stmnt->bindParam(":action", $this->action);
              $stmnt->bindParam(":performed_by", $this->performed_by);
              $stmnt->bindParam(":description", $this->description);
             

              //execute query
              if($stmnt->execute()){
                  return true;
              }
              return false;   
    }

     private function secToHR($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
         return "$hours:$minutes:$seconds";
    }

}
