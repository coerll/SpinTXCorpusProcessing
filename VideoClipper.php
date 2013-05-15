<?php
//Originally prepared by Arthur Wendorf Spring 2013
//Last modified 2 May 2013

//This script prepares a batch file that will generate all of the video clips.

set_time_limit(0);

// Check if the ffmpeg-php extension is loaded first
extension_loaded('ffmpeg') or die('Error in loading ffmpeg');

//This is the file that contains the needed info for each clip to be created.
$clipList = "NewClipData.txt";
$clipHandle = fopen ($clipList, "r") or die ("can't open list of clips");

$arrayOfClips = array();

$newText = "";
$tracker = 1;

function timeDiff($firstTime,$lastTime)
{

	// convert to unix timestamps
	$firstTime=strtotime($firstTime);
	$lastTime=strtotime($lastTime);

	// perform subtraction to get the difference (in seconds) between times
	$timeDiff=$lastTime-$firstTime;

	// return the difference
	return $timeDiff;
}


while(!feof($clipHandle)){
	$newText = fgets($clipHandle);
	$arrayOfClips[$tracker] = explode("	", $newText);
	$tracker++;
}

fclose($clipHandle);

//adds the lines to the batch file.
foreach($arrayOfClips as $entry) {
	$srcFile = "Videos/".$entry[0].".mp4";
	if ($entry[1] <= 9) {
		$destFile = "Clips2/".$entry[0]."_0".$entry[1].".mp4";
	}
	else {
		$destFile = "Clips2/".$entry[0]."_".$entry[1].".mp4";
	}
	$timeDifference = timeDiff("0".$entry[4], "0".$entry[5]);
	exec("ffmpeg -y -ss 0$entry[4] -t $timeDifference -i $srcFile -async 1 $destFile 2>&1", $output);
}
?> 