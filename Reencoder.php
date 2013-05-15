<?php
//Originally prepared by Arthur Wendorf Spring of 2013
//Last modified 2 May 2013

//This script creates the batch file Reencoder.

//The time zone to be used.
date_default_timezone_set('America/Chicago');

//The data will be pulled from and saved here.
$modFile = "MainInput.txt";
$fhmf = fopen($modFile, "r");
$nFile = "Reencoder";
$nh = fopen($nFile, 'w');

//The time format to be used.
$format = 'H:i:s';
$diff = "23:00:00";
$diff2 = strtotime($diff);
$blob = "00:";

$newLine = fgets($fhmf);

//go through each line and prepare the ffmpeg command line for its file.
while (!feof($fhmf)) {
	$newLine = fgets($fhmf);
	$array = mb_split ("[\t]", $newLine);
	if (count($array) > 1) {
		$oFile = "$array[0]-SpinTX-SU2011-SpinTX_HQ.mov";
		$nFile = "$array[0].mp4";
		fwrite($nh, "ffmpeg -i $oFile -f mp4 -vcodec libx264 -acodec libfaac -s 640x368 $nFile\n");
	}
}
fclose($fhmf);
?>