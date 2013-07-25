<?php
set_time_limit(0);
ini_set("auto_detect_line_endings", true);
ini_set('memory_limit', '-1');
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

$source = "NewClipData.txt";
$fsource = fopen($source, 'r');
$dest = "ThumbnailGenerator";
$fdest = fopen($dest, 'w');
$line = "";
//$array = array();
$length = 0;
$i1 = 0;
$i2 = 0;
$i3 = 0;
$diff = 0;
$vid = "";

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

$line = fgets($fsource);

while (!feof($fsource)) {
	$line = fgets($fsource);
	$array = explode("\t", $line);
	$tarSRT = fopen("Processing/Tagged/$array[0].txt", "r");
	while (!feof($tarSRT)) {
		$stepa = fgets($tarSRT);
		$stepb = explode ("	", $stepa);
		if($stepb[8] == $array[3]) {
			$starter = substr($stepb[6], 0, 8);
		}
		else if (intval($stepb[8]) == (intval(trim($array[4])) + 1)) {
			$ender = substr($stepb[6], 0, 8);
		}
	}
	fclose($tarSRT);
	if ($stepb[8] == "1") {
		$starter = "00:00:00";
	}
	$length = timeDiff($starter, $ender);
	$diff = intval($length/6);
	$i1 = $diff;
	$i2 = $diff*3;
	$i3 = $diff*5;
	$vid = "Videos/$array[1].mp4";
	$subvid = $array[1];
	$tn1 = $subvid."_T_1.jpg";
	$tn2 = $subvid."_T_2.jpg";
	$tn3 = $subvid."_T_3.jpg";
	fwrite($fdest, "ffmpeg  -itsoffset -$i1 -i $vid -vframes 1 -an -s 654x368 -y $tn1\n");
	fwrite($fdest, "ffmpeg  -itsoffset -$i2 -i $vid -vframes 1 -an -s 654x368 -y $tn2\n");
	fwrite($fdest, "ffmpeg  -itsoffset -$i3 -i $vid -vframes 1 -an -s 654x368 -y $tn3\n");
	
}
fclose($fsource);
fclose($fdest);
?>