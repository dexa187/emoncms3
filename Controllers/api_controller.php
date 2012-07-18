<?php 
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */
function api_controller()
{
  global $session,$action;
  require "Models/input_model.php";
  require "Models/feed_model.php";
  require "Models/process_model.php";


  // POST arduino posts up to emoncms 				
  if ($action == 'post' && $session['write']) $json = db_real_escape_string($_GET['json']);			
  


  // Brultech
  if ($action == 'brultech')
  {
  $devID = $_GET['dev'];
  $userid = get_id_from_dev($devID);
    //get api ket by device Id
    //post c1w,c2w,A1w,A2w,A3w,A4w,A5w
  $jsona = '{c1w:'.$_GET['c1w'].',c2w:'.$_GET['c2w'].',A1w:'.$_GET['A1w'].',A2w:'.$_GET['A2w'].',A3w:'.$_GET['A3w'].',A4w:'.$_GET['A4w'].',A5w:'.$_GET['A5w'].'}';
  send_data('http','mckervey.com','6000','filename.php',"serial:$devID $jsona");
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=c1w,watts='.$_GET['c1w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=c2w,watts='.$_GET['c2w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=A1w,watts='.$_GET['A1w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=A2w,watts='.$_GET['A2w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=A3w,watts='.$_GET['A3w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=A4w,watts='.$_GET['A4w']);
  send_data('http','mckervey.com','6001','filename.php','serial='.$devID.',device=A5w,watts='.$_GET['A5w']);

 send_data('http','ec2-23-22-244-208.compute-1.amazonaws.com','80','api/post','GET /api/post?apikey=7677241d33bbe2dcbcb856a77ddb4f4a&json='.$jsona); 

  $datapairs = validate_json($jsona);
  $time = time();
  $inputs = register_inputs($userid,$datapairs,$time);
  process_inputs($userid,$inputs,$time);                        // process inputs to feeds etc
  $output = "ok";

  }
  
  if ($json)
  {
    $datapairs = validate_json($json);				// validate json
    $time = time();						// get the time - data recived time
    if (isset($_GET["time"])) $time = intval($_GET["time"]);	// - or use sent timestamp if present
    $inputs = register_inputs($session['userid'],$datapairs,$time);          // register inputs
    process_inputs($session['userid'],$inputs,$time);                        // process inputs to feeds etc
    $output = "ok";
    //$output=$json;
  }

  return $output;
}

  //-------------------------------------------------------------------------
  function register_inputs($userid,$datapairs,$time)
  {

  //--------------------------------------------------------------------------------------------------------------
  // 2) Register incoming inputs
  //--------------------------------------------------------------------------------------------------------------
  $inputs = array();
  foreach ($datapairs as $datapair)       
  {
    $datapair = explode(":", $datapair);
    $name = preg_replace('/[^\w\s-.]/','',$datapair[0]); 	// filter out all except for alphanumeric white space and dash
    $value = floatval($datapair[1]);		

    $id = get_input_id($userid,$name);				// If input does not exist this return's a zero
    if ($id==0) {
      create_input_timevalue($userid,$name,$time,$value);	// Create input if it does not exist
    } else {			
      $inputs[] = array($id,$time,$value);	
      set_input_timevalue($id,$time,$value);			// Set time and value if it does
    }
  }

  return $inputs;
  }

  function process_inputs($userid,$inputs,$time)
  {
  //--------------------------------------------------------------------------------------------------------------
  // 3) Process inputs according to input processlist
  //--------------------------------------------------------------------------------------------------------------
  foreach ($inputs as $input)            
  {
    $id = $input[0];
    $input_processlist =  get_input_processlist($userid,$id);
    if ($input_processlist)
    {
      $processlist = explode(",",$input_processlist);				
      $value = $input[2];
      foreach ($processlist as $inputprocess)    			        
      {
        $inputprocess = explode(":", $inputprocess); 		// Divide into process id and arg
        $processid = $inputprocess[0];				// Process id
        $arg = $inputprocess[1];	 			// Can be value or feed id

        $process_list = get_process_list();
        $process_function = $process_list[$processid][2];	// get process function name
        $value = $process_function($arg,$time,$value);		// execute process function
      }
    }
  }
  }
  
  function send_data($type,$host,$port,$path='/',$data) { 
    $_err = 'lib sockets::'.__FUNCTION__.'(): '; 
    $fp = fsockopen($host,$port,$errno,$errstr,$timeout=2); 
    if($fp){ 
	    //error_log("$data");
        fputs($fp,"$data\r\n");
//	    fputs($fp, "POST $path HTTP/1.1\r\n"); 
 //       fputs($fp, "Host: $host\r\n"); 
 //       fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
 //       fputs($fp, "Content-length: ".strlen($str)."\r\n"); 
 //       fputs($fp, "Connection: close\r\n\r\n"); 
 //       fputs($fp, $str."\r\n\r\n"); 

        //while(!feof($fp)) $d .= fgets($fp,4096); 
        fclose($fp); 
    } return $d; 
} 

?>


