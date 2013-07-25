<?php
//Originally prepared by Arthur Wendorf Spring 2013
//Last modified 2 May 2013

//This script prepares a batch file that will generate all of the video clips.

set_time_limit(0);
ini_set("auto_detect_line_endings", true);

// Check if the ffmpeg-php extension is loaded first
//extension_loaded('ffmpeg') or die('Error in loading ffmpeg');

//This is the file that contains the needed info for each clip to be created.
$clipList = "NewClipData.txt";
$clipHandle = fopen ($clipList, "r") or die ("can't open list of clips");

$splitter = "BatchSplitter";
$splitH = fopen($splitter, 'w') or die ("can't open BatchSplitter");

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
	if ($entry[0] != "interview_id") {
		print_r($entry);
		$srcFile = "Videos/".$entry[0].".mp4";
		if ($entry[2] <= 9) {
			$destFile = "Videos/".$entry[0]."_0".$entry[2];
		}
		else {
			$destFile = "Videos/".$entry[0]."_".$entry[2];
		}
		$tarSRT = fopen("Processing/Tagged/$entry[0].txt", "r");
		while (!feof($tarSRT)) {
			$stepa = fgets($tarSRT);
			$stepb = explode ("	", $stepa);
			if($stepb[8] == $entry[3]) {
				$starter = substr($stepb[6], 0, 8);
			}
			else if (intval($stepb[8]) == (intval(trim($entry[4])) + 1)) {
				$ender = substr($stepb[6], 0, 8);
			}
		}
		fclose($tarSRT);
		if ($stepb[8] == "1") {
			$starter = "00:00:00";
		}
		$timeDifference = timeDiff($starter, $ender);
//		fwrite($splitH, "ffmpeg -y -ss $starter -t $timeDifference -i $srcFile -vcodec copy -acodec copy -async 1 $destFile\n");
//		fwrite($splitH, "ffmpeg -y -ss $starter -t $timeDifference -i $srcFile -async 1 $destFile\n");
		fwrite($splitH, "ffmpeg -t $timeDifference -i $srcFile -ss $starter -vcodec libx264 -acodec libfaac $destFile.Step1.mp4\n");
		fwrite($splitH, "ffmpeg -i $destFile.Step1.mp4 -qscale:v 1 $destFile.Step2.mpg\n");
		fwrite($splitH, "ffmpeg -i "."\""."concat:$destFile.Step2.mpg|Videos/intermediate_all.mpg"."\""." -c copy $destFile.Step3.mpg\n");
		fwrite($splitH, "ffmpeg -i $destFile.Step3.mpg -qscale:v 1 $destFile.mp4\n");
	}
}
fclose($splitH);
?> 