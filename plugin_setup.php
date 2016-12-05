<?php
//$DEBUG=true;

include_once "/opt/fpp/www/common.php";
include_once "functions.inc.php";
include_once 'commonFunctions.inc.php';
$pluginName = "MessageQueue";
$pluginVersion ="2.5";
//$DEBUG=true;
$myPid = getmypid();

$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-MessageQueue.git";


$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";
//2.5 - Dec 3 2016 - Fix update button form
//2.4 - Dec 2 2016 - touch the message queue file
//2.3 - Dec 2 2016 - ability to delete message queue file
//2.2 - Dec 2 - Blacklist functions!

//2.1 - Dec 2 - added dyanimic profnaity file for message queue.



$logFile = $settings['logDirectory']."/".$pluginName.".log";


logEntry("plugin update file: ".$pluginUpdateFile);


if(isset($_POST['updatePlugin']))
{
	$updateResult = updatePluginFromGitHub($gitURL, $branch="master", $pluginName);

	echo $updateResult."<br/> \n";
}


if(isset($_POST['submit']))
{


	//WriteSettingToFile("ENABLED",urlencode($_POST["ENABLED"]),$pluginName);
	WriteSettingToFile("MESSAGE_FILE",urlencode($_POST["MESSAGE_FILE"]),$pluginName);
	

}
$ENABLED = urldecode($pluginSettings['ENABLED']);

$MESSAGE_FILE = urldecode($pluginSettings['MESSAGE_FILE']);

if(trim($MESSAGE_FILE) == "") {
	$MESSAGE_FILE = "/tmp/FPP.MessageQueue";
	//write the default on plugin load if it does not exist!
	
	WriteSettingToFile("MESSAGE_FILE",urlencode($MESSAGE_FILE),$pluginName);
}

if(isset($_POST['delMessageQueue'])) {
	//delete message queue
	logEntry("Deleting message queue file");
	$DELETE_CMD = "/bin/rm ".$MESSAGE_FILE;

	exec($DELETE_CMD);
	
	//touch a new file
	
	$TOUCH_CMD = "/bin/touch ".$MESSAGE_FILE;
	
	exec($TOUCH_CMD);

}



?>

<html>
<head>
</head>

<div id="MessageQueue" class="settings">
<fieldset>
<legend><?php echo $pluginName." Version: ".$pluginVersion;?> Support Instructions</legend>

<p>Known Issues:
<ul>
<li>NONE</li>
</ul>

<p>Configuration:
<ul>
<li>There is no configuration necessary. This plugin supports/allows plugins to communicate and share messages</li>
<li>Current Plugins utilizing MessageQueue:</li>
<li>	SMS Control</li>
<li>	Matrix </li>
<li>	SportsTicker</li>
<li>	Weather</li>
<li>	Election</li>
<li>	Stock Ticker</li>
<li>	RDS To Matrix</li>
<li>	Event Date</li>

</ul>
<p>


<p>To report a bug, please file it against the MessageQueue plugin project on Git: https://github.com/LightsOnHudson/FPP-Plugin-MessageQueue
<form method="post" action="http://<? echo $_SERVER['SERVER_ADDR']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">


<?

$restart=0;
$reboot=0;

echo "ENABLE PLUGIN: ";


PrintSettingCheckbox("Message Queue", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");




echo "<p/> \n";

echo "Message File Path and Name (/tmp/FPP.MessageQueue) : \n";
  
echo "<input type=\"text\" name=\"MESSAGE_FILE\" size=\"64\" value=\"".$MESSAGE_FILE."\"> \n";
echo "<p/> \n";
echo "<hr/> \n";
echo "Message file management \n";
echo "<form name=\"messageManagement\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."?plugin=".$pluginName."&page=plugin_setup.php\"> \n";
echo "<input type=\"submit\" name=\"delMessageQueue\" value=\"Delete Message Queue\"> \n";



?>
<p/>
<input id="submit_button" name="submit" type="submit" class="buttons" value="Save Config">
<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}

echo "</form> \n";
?>
</form>
</fieldset>
</div>
<br />
</html>
