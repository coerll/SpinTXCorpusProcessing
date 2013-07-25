<?php
/* This script is derived from TranscriptPreparerLocal.php.
* The purpose of this script is to prepare the transcript (in the
* form of an srt file) for processing by TreeTagger.
* Originally prepared by Arthur Wendorf during the Spring of 2013.
* Last updated on May 2, 2013.
*/

//These settings will allow the script to run without timing out or encountering Mac-specific errors.
ini_set("auto_detect_line_endings", true);
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

//Creates a file that contains the relevant information for all files in the target folder.
//Creates the new file that will be used for TreeTagger.

$myNewFile = "Processing/Prepared.txt";
$newInput = "SecondInput.txt";
$fi = fopen($newInput, 'r');
$fhNew = fopen($myNewFile, 'w') or die("can't open Prepared\n");

while (!feof ($fi)) {
	$entry = trim(fgets($fi));
	//gathers file ids for all files in folder.
	if ((strlen($entry) > 12) && (strlen($entry) < 28)){
		$newText = file_get_contents("Processing/SRT/".$entry.".txt");
		$x = mb_strstr($newText, "1");
		$newText = mb_substr($newText, $x);
		$newText = mb_ereg_replace("\.\.+", '...', $newText);
		$newText = mb_ereg_replace("\.( \.)+", '...', $newText);
		$newText = mb_ereg_replace('\‘', "'", $newText);
		$newText = mb_ereg_replace('\’', "'", $newText);
		$newText = mb_ereg_replace('\…', "...", $newText);
		$newText = mb_ereg_replace('\“', "'", $newText);
		$newText = mb_ereg_replace('\”', "'", $newText);
		$newText = mb_ereg_replace('\"', "'", $newText);
//		$newText = mb_ereg_replace('--', '...', $newText);
		$newText = mb_ereg_replace('{', '[', $newText);
		$newText = mb_ereg_replace('}', ']', $newText);
		$newText = mb_ereg_replace('\¡', ' ¡ ', $newText);
//		$newText = mb_ereg_replace('\:', ' : ', $newText);
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
		$fh = fopen("Processing/SRT/".$entry.".txt", 'w') or die("can't open entry");
		fwrite($fh, $newText);
		fclose($fh);
		$fh = fopen("Processing/SRT/".$entry.".txt", 'r') or die("can't open entry");
		fwrite ($fhNew, "\nStartFile ".$entry."\n");
		$e2 = trim($entry);
		$e2 = trim($e2, ".txt");
//		fwrite($fi, "\n".$e2);
		$tracker = 0;
		//Converts srts back to plain text files and cleans them up.
		while (!feof($fh)) {
			$newText = fgets($fh);
			$newText = trim($newText);
			if (is_numeric($newText)) {
				$tracker = 0;
			}
			else if ($tracker == 0) {
				$tracker++;
			}
			else if (($tracker > 0) && (mb_strpos($newText, "-->") === false)) {
				if (mb_strpos($newText, ">") !== false) {
					fwrite($fhNew, "\n");
				}
				else {
					fwrite($fhNew, " ");
				}
//				$newText = mb_ereg_replace("\.\.+", '...', $newText);
//				$newText = mb_ereg_replace("\.( \.)+", '...', $newText);
//				$newText = mb_ereg_replace('\‘', "'", $newText);
//				$newText = mb_ereg_replace('\’', "'", $newText);
//				$newText = mb_ereg_replace('\…', "...", $newText);
//				$newText = mb_ereg_replace('\“', "'", $newText);
//				$newText = mb_ereg_replace('\”', "'", $newText);
//				$newText = mb_ereg_replace('\"', "'", $newText);
				$newText = mb_ereg_replace('--', '...', $newText);
//				$newText = mb_ereg_replace('{', '[', $newText);
//				$newText = mb_ereg_replace('}', ']', $newText);
//				$newText = mb_ereg_replace('\¡', ' ¡ ', $newText);
				$newText = mb_ereg_replace('\:', ' : ', $newText);
//				$newText = mb_ereg_replace('\¿', ' ¿ ', $newText);
//				$newText = mb_ereg_replace('\(', ' ( ', $newText);
//				$newText = mb_ereg_replace("([^A-Za-z])(\')", "\\1 ' ", $newText);
//				$newText = mb_ereg_replace("(\')([^A-Za-z])", " ' \\2", $newText);
//				$newText = mb_ereg_replace("([A-Za-z])(\')([A-Za-z])", "\\1 '\\3", $newText);
//				$newText = mb_ereg_replace("\'$", " '", $newText);
//				$newText = mb_ereg_replace("^\'", "' ", $newText);
//				$newText = mb_ereg_replace('\.\.+', '...', $newText);
//				$newText = mb_ereg_replace('\.( \.)+', '...', $newText);
//				$newText = mb_ereg_replace('\.', ' . ', $newText);
//				$newText = mb_ereg_replace('\.  \.  \.', '...', $newText);
				//writes the new transcripts to a file.
				fwrite($fhNew, $newText);
			}
		}
		fclose($fh);
	}
}
fclose($fi);
fclose($fhNew);
?>