<?php
/* This script is designed to run online and to be called by MainBatchMaker.
* The purpose of this script is to prepare the transcript for processing by TreeTagger.
* Originally prepared by Arthur Wendorf during the summer of 2012.
* Last updated on January 15, 2013.
*/

//These setting will allow this php script to run correctly on a Mac.
ini_set("auto_detect_line_endings", true);
mb_internal_encoding("UTF-8");

//Here we indicate what the script should think of as parts of a word.
$characters = "ABCDEFGHIJKLMNÑOPQRSTUVWXYZÁÉÍÓÚÜabcdefghijklmnñopqrstuvwxyzáéíóúü1234567890'-";

//This opens a tab-delimited text file with the following information:
//Interview ID, Location of Transcripts relative to MainBatchMaker.py, YouTube ID
//This information is stored into an array.
$sourceH = fopen("MainInput.txt", "r") or die("TranscriptPreparerLocal.php can't open MainInput.txt.");
$sourceData = array();
$tracker = 0;
while (!feof($sourceH)){
	$sourceData[$tracker] = fgets($sourceH);
	$sourceData[$tracker] = preg_split("/\t/", $sourceData[$tracker]);
	$tracker++;
}

//This determines how many files were uploaded by the user.
$i = $tracker--;

//Creates the new file that will be used for TreeTagger.
$myNewFile = "Processing/Prepared.txt";
$fhNew = fopen($myNewFile, 'w') or die("can't open Prepared\n");

//Initializes the text to be written to the file.
$newText = "";

//Runs through each file, one at a time
for ($n = 1; $n < $i; $n++) {
	
	//opens the file
	$myFile = $sourceData[$n][1];
	$fh = fopen($myFile,'r') or die("can't open file ".$sourceData[$n][1]."\n");
	
	//This is written to the file to show where each new interview begins
	fwrite($fhNew, "\n\nStartFile ".$sourceData[$n][0]."\n\n");
	
	/*This variable is used to keep track of the running character position
	 * It is updated after every line of text and is added to the position
	 * of a character within the current line in order to determine the 
	 * position of the character within the file as a whole.
	 */
	$position = 0;
	
	//This variable is used to keep track of the speaker
	$speaker = ">>u";
	
	//Now we'll loop through each line in the file
	while (!feof($fh)){
		
		//This pulls the text
		$newText = fgets($fh);
				
		//This variable is used to determine whether a character is the last chracter in a word
		$isLast = false;
		
		//This variable is used to determine whether a character is the first chracter in a word
		$isFirst = true;
		
		//This is used to store the line of text as it is worked on, before it is written to the new file
		$new = "";
		
		//Looks to see if the speaker is the interviewer and changes the variable accordingly
		if (mb_strpos($newText, ">> i", 0, "UTF-8") !== false or mb_strpos($newText, ">>i", 0, "UTF-8") !== false or mb_strpos($newText, ">i", 0, "UTF-8") !== false or mb_strpos($newText, "> i", 0, "UTF-8") !== false) {
			$speaker = ">>i";
		}
		elseif (mb_strpos($newText, ">> s", 0, "UTF-8") !== false or mb_strpos($newText, ">>s", 0, "UTF-8") !== false or mb_strpos($newText, ">s", 0, "UTF-8") !== false or mb_strpos($newText, "> s", 0, "UTF-8") !== false) {
			$speaker = ">>s";
		}
		
		//Goes through the line one character at a time
		for ($in = 0; $in < mb_strlen($newText, "UTF-8"); $in++) {
			
			//Determines whether the character is a letter
			if (mb_strpos($characters, mb_substr($newText, $in, 1, "UTF-8")) !== false) {
				
				//Determines whether this is the first letter in a word
				if ($isFirst == true) {
					
					//Adds the letter
					$new = $new." ".mb_substr($newText, $in, 1, "UTF-8");
					
					//Now we are not on the first letter
					$isFirst = false;
					
					//Now we may be on the last letter
					$isLast = true;
				}
				
				//Executes for all letters other than the first
				else {
					
					//adds the letter
					$new = $new.mb_substr($newText, $in, 1, "UTF-8");
				}
			}
			
			//Executes if the current character is not a letter and the preceeding character was a letter
			elseif ($isLast == true) {
				
				//We are no longer within a word
				$isLast = false;
				
				//The next letter will start a new word
				$isFirst = true;
				
				//writes to string: character position, speaker, current character
				$new = $new."	".($in + $position)."	".$speaker."	".mb_substr($newText, $in, 1, "UTF-8");
			}
			
			//Executes if the current character is not a letter and the preceeding character was not a letter
			else {
				
				//adds the current character
				$new = $new.mb_substr($newText, $in, 1, "UTF-8");
			}
		}
		
		//This updates $position so that the character position will be accurate
		$position += mb_strlen($newText, "UTF-8");
		
		//Updates what is to be written to the file
		$newText = $new;
		
		//Lowers the case
		$newText = mb_convert_case($newText, MB_CASE_LOWER, "UTF-8");
		
		//we finally write the line of text to the new file
		fwrite($fhNew, $newText."\n");
	}
	//close the source file
	fclose($fh);
}
	
	
//Closes the new file
fclose($fhNew);
?>