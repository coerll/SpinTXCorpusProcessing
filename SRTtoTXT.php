<?php
set_time_limit(0);
ini_set("auto_detect_line_endings", true);
ini_set('memory_limit', '-1');
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

$fIDs = "MainInput.txt";

$fhIDs = fopen($fIDs, 'r');

$temporary = fgets($fhIDs);

while (!feof($fhIDs)){
	$temporary = fgets($fhIDs);
	$stuff = explode("\t", $temporary);
	$data = file_get_contents("Processing/SRT/".$stuff[0].".srt");
//	$old = fopen("Processing/SRT/".$stuff[0]."srt", 'r');
	$new = fopen("Processing/SRT/".$stuff[0].".txt", 'w');
	fwrite($new, $data);
	fclose($new);
}
?>