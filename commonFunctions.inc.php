<?php

//update plugin

function updatePluginFromGitHub($gitURL, $branch="master", $pluginName) {
	
	
	global $settings;
	logEntry ("updating plugin: ".$pluginName);
	
	logEntry("settings: ".$settings['pluginDirectory']);
	
	//create update script
	//$gitUpdateCMD = "sudo cd ".$settings['pluginDirectory']."/".$pluginName."/; sudo /usr/bin/git git pull ".$gitURL." ".$branch;

	$pluginUpdateCMD = "/opt/fpp/scripts/update_plugin ".$pluginName;

	logEntry("update command: ".$pluginUpdateCMD);


	exec($pluginUpdateCMD, $updateResult);

	//logEntry("update result: ".print_r($updateResult));

	//loop through result	
	return;// ($updateResult);
	
	
	
}
function logEntry($data,$logLevel=1,$sourceFile, $sourceLine) {

	global $logFile,$myPid, $LOG_LEVEL;

	
	if($logLevel <= $LOG_LEVEL) 
		//return
		
		if($sourceFile == "") {
			$sourceFile = $_SERVER['PHP_SELF'];
		}
		$data = $sourceFile." : [".$myPid."] ".$data;
		
		if($sourceLine !="") {
			$data .= " (Line: ".$sourceLine.")";
		}
		
		$logWrite= fopen($logFile, "a") or die("Unable to open file!");
		fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
		fclose($logWrite);


}
?>
