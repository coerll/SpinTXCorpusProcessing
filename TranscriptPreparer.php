<?php
/* This script is designed to run online and to be called by TranscriptUploader.html.
* The purpose of this script is to prepare the transcript for processing by TreeTagger.
* Originally prepared by Arthur Wendorf during the summer of 2012.
* Last updated on August 30, 2012.
*/

//This determines how many files were uploaded by the user.
$i = count($_FILES['file']['tmp_name']);

//This iterates through each file, creates a path for it, and copies it to that location.
for ($n = 1; $n <= $i; $n++) {
	$path="uploads/".$_FILES['file']['name'][$n-1];
	copy($_FILES['file']['tmp_name'][$n-1],$path);
}

//Variables for lowering case
$chars_hi = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZÁÉÍÓÚÜ';
$chars_lo = 'abcdefghijklmnñopqrstuvwxyzáéíóúü';

//Lowers the case
function lowercase($s) {
	global $chars_hi, $chars_lo;
	return strtr($s, $chars_hi, $chars_lo);
}

//Creates the new file that will be used for TreeTagger.  File name includes the date.
$date = getdate();
$myNewFile = "uploads/Prepared_".$date["month"]."_".$date["mday"]."_".$date["year"].".txt";
$fhNew = fopen($myNewFile, 'w') or die("can't open new file");

//Initializes the text to be written to the file.
$newText = "";

//Runs through each file, one at a time
for ($n = 1; $n <= $i; $n++) {
	
	//opens the file
	$myFile = "uploads/".$_FILES['file']['name'][$n-1];
	$fh = fopen($myFile,'r') or die("can't open original file");
	
	//This is written to the file to show where each new interview begins
	fwrite($fhNew, "\n\nStartFile ".$_FILES['file']['name'][$n-1]."\n\n");
	
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
		
		//changes encoding from UTF-8 to ANSI
		$newText = iconv("UTF-8", "WINDOWS-1252//TRANSLIT", $newText);
		
		//This variable is used to determine whether a character is the last chracter in a word
		$isLast = false;
		
		//This variable is used to determine whether a character is the first chracter in a word
		$isFirst = true;
		
		//This is used to store the line of text as it is worked on, before it is written to the new file
		$new = "";
		
		//Looks to see if the speaker is the interviewer and changes the variable accordingly
		if (strpos($newText, ">> i") !== false or strpos($newText, ">>i") !== false or strpos($newText, ">i") !== false or strpos($newText, "> i") !== false) {
			$speaker = ">>i";
		}
		elseif (strpos($newText, ">> s") !== false or strpos($newText, ">>s") !== false or strpos($newText, ">s") !== false or strpos($newText, "> s") !== false) {
			$speaker = ">>s";
		}
		
		//Goes through the line one character at a time
		for ($in = 0; $in < strlen($newText); $in++) {
			
			//Determines whether the character is a letter
			if (strpos("ABCDEFGHIJKLMNÑOPQRSTUVWXYZÁÉÍÓÚÜabcdefghijklmnñopqrstuvwxyzáéíóúü1234567890'-", $newText[$in]) !== false) {
				
				//Determines whether this is the first letter in a word
				if ($isFirst == true) {
					
					//Adds the letter
					$new = $new." ".$newText[$in];
					
					//Now we are not on the first letter
					$isFirst = false;
					
					//Now we may be on the last letter
					$isLast = true;
				}
				
				//Executes for all letters other than the first
				else {
					
					//adds the letter
					$new = $new.$newText[$in];
				}
			}
			
			//Executes if the current character is not a letter and the preceeding character was a letter
			elseif ($isLast == true) {
				
				//We are no longer within a word
				$isLast = false;
				
				//The next letter will start a new word
				$isFirst = true;
				
				//writes to string: character position, speaker, current character
				$new = $new."	".($in + $position)."	".$speaker."	".$newText[$in];
			}
			
			//Executes if the current character is not a letter and the preceeding character was not a letter
			else {
				
				//adds the current character
				$new = $new.$newText[$in];
			}
		}
		
		//This updates $position so that the character position will be accurate
		$position += strlen($newText);
		
		//Updates what is to be written to the file
		$newText = $new;
		
		//Lowers the case
		$newText = lowercase($newText);
		
		//we finally write the line of text to the new file
		fwrite($fhNew, $newText."\n");
	}
}
	
	//close the source file
	fclose($fh);
	
//Closes the new file
fclose($fhNew);

//Downloads the file to the user's computer
if (file_exists($myNewFile)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($myNewFile));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($myNewFile));
    ob_clean();
    flush();
    readfile($myNewFile);
    exit;
}

?>