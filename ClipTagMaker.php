<?php

ini_set("auto_detect_line_endings", true);
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');
date_default_timezone_set('America/Chicago');

$modFile = "NewClipData.txt";
$fhmf = fopen($modFile, "r");
$fn = fopen('Processing/Tagged/AllClips.txt', 'w');
fwrite($fn, "Clip ID	Word Number	Original Word	TreeTagger POS	POS	Lemma	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n");

$format = 'H:i:s';
$diff = "23:00:00";
$diff2 = strtotime($diff);
$blob = "00:";

$first = 0;

while (!feof($fhmf)) {
	if ($first == 0) {
		$first = 1;
		$newLine = fgets($fhmf);
	}
	else {
		$newLine = fgets($fhmf);
		$array = mb_split ("[\t]", $newLine);
		if (count($array) > 1) {
			$start = intval($array[3]);
			$end = intval($array[4]);
			$file = "Processing/Tagged/$array[0].txt";
//			echo $array[0]."  ";
			$tempfh = fopen("Processing/Tagged/$array[1].txt", 'w');
			$fh = fopen($file,'r') or die("can't open original file");
			$newText = fgets($fh);
			fwrite($tempfh, "Clip ID	Word Number	Original Word	TreeTagger POS	POS	Lemma	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n");
			$base = 0;
			while (!feof($fh)){
				$newText2 = fgets($fh);
				$newText = trim($newText2);
				$lineArray = mb_split("\t", $newText);
				if (count($lineArray) > 1) {
					if (intval($lineArray[8]) >= $start && intval($lineArray[8]) <= $end) {
						if ($base == 0){
							$bsTime = $lineArray[6];
							$bsTime = substr($bsTime,0,8);
							$bsTime = strtotime($bsTime);
							$bLine = intval($lineArray[8]) - 1;
							$base = 1;
						}
						$lineArray[0] = "tt-".$lineArray[0];
						$osTime = $lineArray[6];
						$osTime = substr($osTime,0,8);
						$osTime = strtotime($osTime);
						$nsTime = $osTime - $bsTime;
						$nsTime = date('H:i:s', $nsTime);
						$oeTime = $lineArray[7];
						$oeTime = substr($oeTime,0,8);
						$oeTime = strtotime($oeTime);
						$neTime = $oeTime - $bsTime;
						$neTime = date('H:i:s', $neTime);
						$Line = intval($lineArray[8]);
						fwrite($tempfh, "$array[1]	$lineArray[0]	$lineArray[1]	$lineArray[2]	$lineArray[3]	$lineArray[4]	$lineArray[5]	00:".substr($nsTime,3,5)."	00:".substr($neTime,3,5)."	".strval($Line - $bLine)."	$lineArray[9]	$lineArray[10]	$lineArray[11]	$lineArray[12]	$lineArray[13]	$lineArray[14]\n");
						fwrite($fn, "$array[1]	$lineArray[0]	$lineArray[1]	$lineArray[2]	$lineArray[3]	$lineArray[4]	$lineArray[5]	00:".substr($nsTime,3,5)."	00:".substr($neTime,3,5)."	".strval($Line - $bLine)."	$lineArray[9]	$lineArray[10]	$lineArray[11]	$lineArray[12]	$lineArray[13]	$lineArray[14]\n");
						
					}
				}
			}
			fclose($tempfh);
			fclose($fh);
		}
	}
}
fclose($fhmf);
fclose($fn);
?>