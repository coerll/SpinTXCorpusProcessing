<?php
ini_set("auto_detect_line_endings", true);
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
date_default_timezone_set('America/Chicago');
$oFile = "NewClipData.txt";

set_time_limit(0);

$clipHandle = fopen ($oFile, "r") or die ("can't open list of clips");

$arrayOfClips = array();
$tracker = 0;
$format = 'H:i:s';

while(!feof($clipHandle)){
	$newText = fgets($clipHandle);
	$arrayOfClips[$tracker] = mb_split ("[\t]", $newText);
	$tracker++;
}

fclose($clipHandle);

foreach ($arrayOfClips as $Clip) {
	if ($Clip[0] != "Clip" and count($Clip) >= 4) {
		if (file_exists("Processing/SRT/".$Clip[0].".txt")) {
			$fh = fopen("Processing/SRT/".$Clip[0].".txt", "r");
			$fhn = fopen("Processing/SRT/".$Clip[1].".srt", "w");
			$tracker = 0;
			$start = $Clip[3];
			$end = $Clip[4];
			$lineDiff = $start - 1;
			while(!feof($fh)){
				$newText2 = fgets($fh);
				$newText2 = preg_replace('/\x{EF}\x{BB}\x{BF}/','',$newText2);
				if (trim($newText2) != "") {  //its comparing srt line num with time
					if ($tracker == 0) {
						if (intval($newText2) == intval($start)) {
							$found = true;
//							$timeDiff = strtotime($Clip[1]);//fix here
						}
						if (intval($newText2) >= intval($start) and intval($newText2) <= intval($end)) {
							$tracker = 1;
							fwrite($fhn, "\n".(intval($newText2) - $lineDiff)."\n");
						}
					}
					elseif ($tracker == 1) {
						$oldStart = strtotime(substr($newText2, 0, 8));
						if ($found === true) {
							$timeDiff = $oldStart;
							$found = false;
						}
						$newStart = ($oldStart - $timeDiff);
						$temp = date($format, $newStart);
						$temp2 = intval(substr($temp,0,2));
						fwrite($fhn, "00:");//.$temp2);
						fwrite($fhn, substr(date($format, $newStart),3,5));					
						fwrite($fhn, ",000 --> ");
						$oldStart = strtotime(substr($newText2, 17, 8));
						$newStart = ($oldStart - $timeDiff);
						$temp = date($format, $newStart);
						$temp2 = intval(substr($temp,0,2));
						fwrite($fhn, "00:");//.$temp2);
						fwrite($fhn, substr(date($format, $newStart),3,5));
						fwrite($fhn, ",000\n");
						$tracker++;
					}
					else {
						$newText2 = trim($newText2);
						fwrite($fhn, $newText2."\n");
					}
				}
				elseif ($tracker >= 1) {
				//	fwrite($fhn, $newText2);
					$tracker = 0;
				}
			}
		}
	}
}
?>