<?php

//add message to queue

function addNewMessage($messageText,$pluginName,$pluginData="") {

	global $messageQueueFile;


	logEntry("MESSAGEQUEUE_PLUGIN: Adding message to message queue: ".$messageQueue." : ".$messageText." :".$pluginName." :".$pluginData);


	$messageLine = "";

	$messageLine = time()."| ".$messageText . " | ".$pluginName. " | ".$pluginData."\n";
	//$messageLine = date('Y-m-d h:i:s A',time())."| ".$messageText . " | ".$pluginName. " | ".$pluginData."\n";

	file_put_contents($messageQueueFile, $messageLine, FILE_APPEND | LOCK_EX);


}


//get new messages.. write a status file with the plugin name of the last time messages were read
//only get messages from plugins that it wants to subscribe to

function getNewPluginMessages($subscriptions="") {
	
	global $pluginName,$messageQueuePluginPath,$messageQueueFile;

	$newMessages=array();
	//reset the julian to empty
	$pluginLastRead= 0;
	$pluginSubscriptions = array();

	logEntry("getting new messages for plugin: ".$pluginName);
if($subscriptions == "") {
	$pluginSubscriptions[] = $pluginName;
} else {

	$pluginSubscriptions = explode(",",$subscriptions);
}
echo "plugin subscriptions array: \n";
print_r($pluginSubscriptions);

if(file_exists($messageQueuePluginPath.$pluginName.".lastRead"))
        {
		$pluginLastRead = file_get_contents($messageQueuePluginPath.$pluginName.".lastRead");
		echo "plugin last read: ".$pluginLastRead."\n";

	} else {

		logEntry("no last read messages for plugin: ".$pluginName. " getting all messages");

        }

if(file_exists($messageQueueFile))
	{
		$messagesTemp = file_get_contents($messageQueueFile);

	} else {
		logEntry("No message queue to process");
		return;
	}
	$pluginMessageQueue = explode("\n",$messagesTemp);

	print_r($pluginMessageQueue);

	$pluginLatest ="";
	//get the lastest number and write it to the last read file

	$i=0;

	for($i=0;$i<count($pluginMessageQueue)-1;$i++) 
	{
		echo $i."\n";
		$pluginLatest = substr($pluginMessageQueue[$i],0,10);

	}

	echo "message queue latest: ".$pluginLatest."\n";

	file_put_contents($messageQueuePluginPath.$pluginName.".lastRead",$pluginLatest);



	//check to see the index of the messages that we need to look at now

	$messageIndex = 0;

	for($messageIndex = 0 ;$messageIndex < count($pluginMessageQueue)-1;$messageIndex++) 

	{

		$messageQueueParts = explode("|",$pluginMessageQueue[$messageIndex]);
		print_r($messageQueueParts);

		if($messageQueueParts[0] > $pluginLastRead && in_array(trim($messageQueueParts[2]),$pluginSubscriptions))
		{
			echo "we have a new message and subscriptions matches";	
			//add message to new queue
			$newMessages[]=$pluginMessageQueue[$messageIndex];
		} else {
			

			echo "message: ".$pluginMessageQueue[$messageIndex]. " is not newer then last read or is not a subscription\n";
		} 


	}

	if(count($newMessages)>0) {
		echo "New messages found: \n";
		print_r($newMessages);
	}

	return $newMessages;
}	

?>
