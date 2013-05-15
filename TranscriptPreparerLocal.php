<?php
/* This script is designed to run online and to be called by MainBatchMaker.
* The purpose of this script is to prepare the transcript for processing by TreeTagger.
* Originally prepared by Arthur Wendorf during the summer of 2012.
* Last updated on May 2, 2013.
*/

//These setting will allow this php script to run correctly on a Mac.
ini_set("auto_detect_line_endings", true);
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

//This opens a tab-delimited text file with the following information:
//Interview ID, Location of Transcripts relative to MainBatchMaker.py, YouTube ID
//This information is stored into an array.
$sourceH = fopen("MainInput.txt", "r") or die("TranscriptPreparerLocal.php can't open MainInput.txt.");
$sourceData = array();
$tracker = 0;
$temp = array();
$temp2 = "";
while (!feof($sourceH)){
	$sourceData[$tracker] = fgets($sourceH);
	$temp = mb_split('\t', $sourceData[$tracker]);
	if (strlen($temp[0]) > 12) {
		$sourceData[$tracker] = $temp[0];
		$tracker++;
	}
}

//Creates the new file that will be used for TreeTagger.
$myNewFile = "Processing/Prepared.txt";
$fhNew = fopen($myNewFile, 'w') or die("can't open Prepared\n");

mb_ereg_replace("[^A-Za-z0-9\.\-]","",$data);

//Initializes the text to be written to the file.
$newText = "";
//Runs through each file, one at a time, and cleans it up.
if ($handle = opendir('transcripts/')) {
	while (false !== ($entry = readdir($handle))) {
		if (strlen($entry) > 12){
			$fh = fopen("transcripts/".$entry, 'r') or die("can't open entry");
			$newText = file_get_contents("transcripts/".$entry);
			$newText = mb_ereg_replace("\.\.+", '...', $newText);
			$newText = mb_ereg_replace("\.( \.)+", '...', $newText);
			$newText = mb_ereg_replace('\‘', "'", $newText);
			$newText = mb_ereg_replace('\’', "'", $newText);
			$newText = mb_ereg_replace('\…', "...", $newText);
			$newText = mb_ereg_replace('\“', "'", $newText);
			$newText = mb_ereg_replace('\”', "'", $newText);
			$newText = mb_ereg_replace('\"', "'", $newText);
			$newText = mb_ereg_replace('--', '...', $newText);
			$newText = mb_ereg_replace('{', '[', $newText);
			$newText = mb_ereg_replace('}', ']', $newText);
			fclose($fh);
			unlink("transcripts/".$entry);
			$fh = fopen("transcripts/".$entry,'w') or die("can't open file ".$entry."\n");
			fwrite($fh, $newText);
			$temp2 = mb_substr($entry, 0, 23);
			//For those files in MainInput, prepares them to be TreeTagged.
			if (in_array($temp2, $sourceData)){
				fwrite($fhNew, "\nStartFile ".mb_strcut($entry, 0, 23, "UTF-8")."\n");
				$newText = mb_ereg_replace('\¡', ' ¡ ', $newText);
				$newText = mb_ereg_replace('\:', ' : ', $newText);
				$newText = mb_ereg_replace('\¿', ' ¿ ', $newText);
				$newText = mb_ereg_replace('\(', ' ( ', $newText);
				$newText = mb_ereg_replace("([^A-Za-z])(\')", "\\1 ' ", $newText);
				$newText = mb_ereg_replace("(\')([^A-Za-z])", " ' \\2", $newText);
				$newText = mb_ereg_replace("([A-Za-z])(\')([A-Za-z])", "\\1 '\\3", $newText);
				$newText = mb_ereg_replace("\'$", " '", $newText);
				$newText = mb_ereg_replace("^\'", "' ", $newText);
				$newText = mb_ereg_replace('\.\.+', '...', $newText);
				$newText = mb_ereg_replace('\.( \.)+', '...', $newText);
				$newText = mb_ereg_replace('\.', ' . ', $newText);
				$newText = mb_ereg_replace('\.  \.  \.', '...', $newText);
				fwrite($fhNew, $newText."\n");
			}
			fclose($fh);
		}
	}
}
	
//Closes the new file
fclose($fhNew);
?>