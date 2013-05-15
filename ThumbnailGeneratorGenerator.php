<?php
$source = "BatchSplitter";
$fsource = fopen($source, 'r');
$dest = "ThumbnailGenerator";
$fdest = fopen($dest, 'w');
$line = "";
$array = array();
$length = 0;
$i1 = 0;
$i2 = 0;
$i3 = 0;
$diff = 0;
$vid = "";

while (!feof($fsource)) {
	$line = fgets($fsource);
	$array = explode(" ", $line);
	$length = $array[4];
	$diff = intval($length/6);
	$i1 = $diff;
	$i2 = $diff*3;
	$i3 = $diff*5;
	$vid = "Videos/".trim($array[11]);
	$subvid = substr($array[11], 0, 26);
	$tn1 = $subvid."_T_1.jpg";
	$tn2 = $subvid."_T_2.jpg";
	$tn3 = $subvid."_T_3.jpg";
	fwrite($fdest, "ffmpeg  -itsoffset -$i1 -i $vid -vframes 1 -an -s 640x368 -y $tn1\n");
	fwrite($fdest, "ffmpeg  -itsoffset -$i2 -i $vid -vframes 1 -an -s 640x368 -y $tn2\n");
	fwrite($fdest, "ffmpeg  -itsoffset -$i3 -i $vid -vframes 1 -an -s 640x368 -y $tn3\n");
	
}
fclose($fsource);
fclose($fdest);
?>