<?php

//add message to queue

function addNewMessage($messageText,$pluginName,$pluginData="",$messageFile) {

	global $messageQueueFile;

	if($messageFile == "") {
		$messageFile = $messageQueueFile;
	}
	//logEntry("MESSAGEQUEUE_PLUGIN: Message File: ".$messageQueueFile);
	logEntry("Message queue file: ".$messageFile);

	logEntry("MESSAGEQUEUE_PLUGIN: Adding message to message queue: ".$messageText." :".$pluginName." :".$pluginData);


	$messageLine = "";

	$messageLine = time()."| ".urlencode($messageText) . " | ".$pluginName. " | ".$pluginData."\n";
	//$messageLine = date('Y-m-d h:i:s A',time())."| ".$messageText . " | ".$pluginName. " | ".$pluginData."\n";

	//echo "writing message line \r\n".$messageLine;
	
	file_put_contents($messageFile, $messageLine, FILE_APPEND | LOCK_EX);

}


//get new messages.. write a status file with the plugin name of the last time messages were read
//only get messages from plugins that it wants to subscribe to

function getNewPluginMessages($subscriptions="") {
	
	global $messageQueuePluginPath,$messageQueueFile;
	
	if(!file_exists($messageQueueFile))
	{
		logEntry("No message queue file exists to process: ".$messageQueueFile);
		return null;
	}

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
	
	logEntry("MessageQueuePlugin: getting new messages for plugin: ".$pluginSubscriptions[$pluginIndex]);
	

		$pluginLastRead  = urldecode(ReadSettingFromFile("LAST_READ",$pluginSubscriptions[$pluginIndex]));
		logEntry("plugin ".$pluginSubscriptions[$pluginIndex]." last read: ".$pluginLastRead);

		if((int)$pluginLastRead == 0 || $pluginLastRead == "") 
		{
			logEntry("last read =0 or no last read messages for plugin: ".$pluginSubscriptions[$pluginIndex]. " getting all messages");
			$pluginLastRead=0;
        }
        
		$messagesTemp = file_get_contents($messageQueueFile);
		
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

		//logEntry("message queue latest: ".$pluginLatest);
		logEntry("Writing high water mark for plugin: ".$pluginSubscriptions[$pluginIndex]." LAST_READ = ".$pluginLatest);

		//file_put_contents($messageQueuePluginPath.$pluginSubscriptions[$pluginIndex].".lastRead",$pluginLatest);
		WriteSettingToFile("LAST_READ",urlencode($pluginLatest),$pluginSubscriptions[$pluginIndex]);
		//check to see the index of the messages that we need to look at now

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
	
		if(count($newMessages)>0) {
		
			//echo "Plugin: ".$pluginSubscriptions[$pluginIndex]." -----------NEW MESSAGES\n";
			//echo "New messages found: \n";
			//print_r($newMessages);
			logEntry("New Messages found for ".$pluginSubscriptions[$pluginIndex]);
			//print_r($newMessages);
		
		}	else {
			logEntry("No new messages for plugin: ".$pluginSubscriptions[$pluginIndex]);
		}
	}

	

return $newMessages;
}



//only get the messages for a given plugin to help manage on the plugin's page. can pass a last read paramater to get messages SINCE the last read
//plugins using this functionality = SMS
//dec 18 2015

function getPluginMessages($subscriptions="", $pluginLastRead=0, $messageFile="") {

	global $messageQueuePluginPath,$messageQueueFile, $pluginName;

	
	
	if(!file_exists($messageQueueFile))
	{
		logEntry("No message queue file exists to process: ".$messageQueueFile);
		return null;
	}
	
	if($messageFile != "") {
		$messageFile = $messageQueueFile;// = $messageFile;
	}

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

		logEntry("MessageQueuePlugin: getting new messages for plugin: ".$pluginSubscriptions[$pluginIndex]);


	
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
