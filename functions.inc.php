<?php
//create message tables


function createTables() {
	global $db, $Plugin_DBName;

	$createQuery = "CREATE TABLE IF NOT EXISTS messages (messageID INTEGER PRIMARY KEY AUTOINCREMENT, timestamp int(16) NOT NULL, message varchar(255), pluginName varchar(64), pluginData varchar(64));";

	logEntry("CREATING Messages in db: ".$Plugin_DBName.": ".$createQuery);

	$db->exec($createQuery) or die('Create Table Failed');

	
}

function insertMessage($DBName, $table, $message, $pluginName, $pluginData) {
	global $db;

	$db = new SQLite3($DBName) or die('Unable to open database');
	
	$insertQuery = "INSERT INTO ".$table." (timestamp, message, pluginName, pluginData) VALUES ('".time()."','".urlencode($message)."','".$pluginName."','".urlencode($pluginData)."');";

	logEntry("MESSAGEQUEUE_PLUGIN: INSERT query string: ".$insertQuery);
	$db->exec($insertQuery) or die('could not insert into database');


}
//add message to queue

function addNewMessage($messageText,$pluginName,$pluginData="",$messageFile) {

	global $messageQueueFile, $TwilioVersion, $settings, $WeatherVersion;
	
	if($pluginName == "TwilioControl") {
		$pluginVersion = $TwilioVersion;
	} elseif($pluginName == "Weather") {
		$pluginVersion = "2.0";
	} elseif($pluginName == "SportsTicker") {
		$pluginVersion = "2.0";
	}

	logEntry("Message file passed: ".$messageFile);
	if($messageFile == "") {
		$messageFile = $messageQueueFile;
	}
	//logEntry("MESSAGEQUEUE_PLUGIN: Message File: ".$messageQueueFile);
	logEntry("MESSAGEQUEUE_PLUGIN: Message queue file: ".$messageFile);

	logEntry("MESSAGEQUEUE_PLUGIN: Adding message to message queue: ".$messageText." :".$pluginName." :".$pluginData);


	switch ($pluginVersion) {
		
		case "2.0":
			
			insertMessage($messageFile, "messages", $messageText, $pluginName, $pluginData);
			
			break;
			
		default;
		
			$messageLine = "";
			
			$messageLine = time()."| ".urlencode($messageText) . " | ".$pluginName. " | ".$pluginData."\n";
			//$messageLine = date('Y-m-d h:i:s A',time())."| ".$messageText . " | ".$pluginName. " | ".$pluginData."\n";
			
			//echo "writing message line \r\n".$messageLine;
			
			file_put_contents($messageFile, $messageLine, FILE_APPEND | LOCK_EX);
	}


}


//get new messages.. write a status file with the plugin name of the last time messages were read
//only get messages from plugins that it wants to subscribe to

function getNewPluginMessages($subscriptions="") {
	
	global $DEBUG, $messageQueuePluginPath,$messageQueueFile, $TwilioVersion, $MatrixMessageVersion;
	
	if($DEBUG)
		logEntry("MESSAGE QUEUE: Inside function ".__METHOD__,0,__FILE__,__LINE__);
	
	$pluginVersion = $TwilioVersion;
	$pluginVersion = $MatrixMessageVersion;
	$pluginVersion = "2.0";
	
	logEntry("Plugin version: ".$pluginVersion);
	
	switch ($pluginVersion) {
		
		case "2.0":
			
			
			
			if($subscriptions == "") {
				$pluginSubscriptions[] = $pluginName;
			} else {
			
				$pluginSubscriptions = explode(",",$subscriptions);
			}
			
			
			//build list of messages from the pluginSubscriptions array
			$newMessages=array();
			$pluginLastRead= 0;
			foreach($pluginSubscriptions as $pluginName) {
				//require("/opt/fpp/www/common.php");
				$DB_NAME = $settings['configDirectory']."/FPP.".$pluginName.".db";
				$DB_NAME = "/home/fpp/media/config"."/FPP.".$pluginName.".db";
				
				logEntry("MESSAGE QUEUE: getting NEW ".$pluginName." messages from ".$pluginName." ".$DB_NAME." DB");
				
				$pluginLastRead  = urldecode(ReadSettingFromFile("LAST_READ",$pluginName));
				logEntry("MESSAGE QUEUE: plugin ".$pluginName." last read: ".$pluginLastRead);
				
				$db = new SQLite3($DB_NAME) or die("Unable to open ".$pluginName." database");
				
				//using > ONLY. caused the last message to be repeated, repeated, repeated because it was = the last read!
				//if TWO messages arrive with the EXACT same timestamp. this could cause one message to possibly be missed, 
				//highly unlikely
				
				$messagesQuery = "SELECT * FROM messages WHERE pluginName = '".$pluginName."' AND timestamp > '".$pluginLastRead."'";
				
				if($DEBUG) {
					logEntry("MESSAGE QUEUE: New Messages query: ".$messagesQuery);
				}
				$messagesResult = $db->query($messagesQuery) or die('Query failed');
				
				while ($row = $messagesResult->fetchArray()) {
					
					logEntry("MESSAGE QUEUE: Message found: ".$row['timestamp']." ".$row['message']." ".$row['pluginName']." ".$row['pluginData']);
					
					//add message to array
					$newMessages[] = $row['timestamp']."|".$row['message']."|".$pluginName."|".$row['pluginData'];
					//update the last read with each incremental read
					
					$pluginLastRead = $row['timestamp'];
					logEntry("MESSAGEQUEUE: Plugin: ".$pluginName." last read set to: ".$pluginLastRead);
					
				}
				
				logEntry("MESSAGE QUEUE: Writing high water mark for plugin: ".$pluginName." LAST_READ = ".$pluginLastRead);
				
				
				WriteSettingToFile("LAST_READ",urlencode($pluginLastRead),$pluginName);
				
			//	$db.close();
				
			}
			
			if($DEBUG) {
				logEntry("MESSAGE QUEUE: Wrote high water mark",0,__FILE__,__LINE__);
			}
			//debug to output messages to log file
			
			if($DEBUG) {
				foreach($newMessages as $tmpMsg) {
					logEntry("MESSAGE QUEUE PENDING QUEUE: ".$tmpMsg);
				}
			}
			return $newMessages;
			
			break;
			
			
		default:
			
			break;
			
	}
	
}



//only get the messages for a given plugin to help manage on the plugin's page. can pass a last read paramater to get messages SINCE the last read
//plugins using this functionality = SMS
//dec 18 2015

function getPluginMessages($subscriptions="", $pluginLastRead=0, $messageFile="") {

	global $messageQueuePluginPath,$messageQueueFile, $pluginName, $TwilioVersion;

	$db = new SQLite3($messageQueueFile) or die('Unable to open database');
	// set up DB connection
	//$DB_NAME = "/tmp/FPP." . $pluginName . ".db";
	
	//$db = new SQLite3 ( $DB_NAME ) or die ( 'Unable to open database' );
	
	$pluginVersion = $TwilioVersion;
	
	switch ($pluginVersion) {
		
		case "2.0":
			$result = $db->query('SELECT * FROM messages WHERE pluginName =\'".$subscriptions."') or die('Query failed');
			return $result;//->fetchArray();
			break;
			
		default;
		
			break;
		
	}
	
	
	if(!file_exists($messageQueueFile))
	{
		logEntry("No message queue file exists to process: ".$messageQueueFile);
		return null;
	}
	
	if($messageFile == "") {
		$messageFile = $messageQueueFile;// = $messageFile;
	}

	logEntry("Getting plugin messages from: ".$messageFile);
	
	$newMessages=array();
	//reset the julian to empty
	$pluginLastRead= 0;
	$pluginSubscriptions = array();


	if($subscriptions == "") {
		$pluginSubscriptions[] = $pluginName;
	} else {

		$pluginSubscriptions = explode(",",$subscriptions);
	}

	//print_r($pluginSubscriptions);
	//loop through all the subscriptions that this plugin reader needs to do and append all messages!!!

	$i=0;
	for($pluginIndex=0;$pluginIndex<=count($pluginSubscriptions)-1;$pluginIndex++) {

		logEntry("MessageQueuePlugin: getting messages for plugin: ".$pluginSubscriptions[$pluginIndex]);


	
		logEntry("plugin ".$pluginSubscriptions[$pluginIndex]." last read: ".$pluginLastRead);

		if((int)$pluginLastRead == 0 || $pluginLastRead == "")
		{
			logEntry("last read =0 or no last read messages for plugin: ".$pluginSubscriptions[$pluginIndex]. " getting all messages");
			$pluginLastRead=0;
		}

		$messagesTemp = file_get_contents($messageFile);

		$pluginMessageQueue = explode("\n",$messagesTemp);
		//print_r($pluginMessageQueue);

		//print_r($pluginMessageQueue);

		$pluginLatest ="0";
		//get the lastest number and write it to the last read file

		$i=0;

		for($i=0;$i<count($pluginMessageQueue)-1;$i++)
		{
			//	echo $i."\n";
			$pluginLatest = substr($pluginMessageQueue[$i],0,10);

		}

		

		$messageIndex = 0;

		for($messageIndex = 0 ;$messageIndex < count($pluginMessageQueue)-1;$messageIndex++)

		{

			$messageQueueParts = explode("|",$pluginMessageQueue[$messageIndex]);
			//	print_r($messageQueueParts);

			//	print_r($pluginSubscriptions);

			//echo "MessageQueueParts: ".$messageQueueParts[0]. " -- ".$pluginLastRead."\n";
			//echo "MessageQueueParts: ".$messageQueueParts[2]. " --".$pluginSubscriptions[$pluginIndex]."\n";
			if($messageQueueParts[0] > $pluginLastRead && strtoupper(trim($messageQueueParts[2])) == strtoupper($pluginSubscriptions[$pluginIndex]))
			{
				//echo "we have a new message and subscriptions matches";
				//add message to new queue
				$newMessages[]=$pluginMessageQueue[$messageIndex];
			} else {
					
				//this is VERY verbose
				//logEntry("message: ".$pluginMessageQueue[$messageIndex]. " is not newer then last read or is not a subscription");
			}


		}

		
	}



	return $newMessages;
}
?>
