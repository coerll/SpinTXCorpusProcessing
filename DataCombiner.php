<?php
/* The purpose of this script is to combine the timing data and TreeTagger data and to tag for additional information.
 * This script was originally prepared by Arthur Wendorf during the summer of 2012.
 * Last updated on August 31, 2012.
 */

//These are the variables used for lowering case
$chars_hi = 'ABCDEFGHIJKLMNÑOPQRSTUVWXYZÁÉÍÓÚÜ';
$chars_lo = 'abcdefghijklmnñopqrstuvwxyzáéíóúü';

//these are the POS tags used for punctuation
$aValid = array("CM", "COLON", "DASH", "DOTS", "FS", "QT", "SEMICOLON");

//these are the things to look out for when searching for words
$bad = array('‘', '’', "…", '“', '”', ">>i", ">>s", ">> i", ">> s", ">i", "> i", ">s", "> s", ">>", "%", '.', ",", "!", "?", ":", ";", "¡", "¿", '(', ')', '[', ']', '"');

//this will be used to notify of errors that are found during tagging
$log = "Error log\n";

//This puts a string in all lowercase.
function lowercase($s) {
	
	//calls the variables
	global $chars_hi, $chars_lo;
	
	//swaps the characters
	return strtr($s, $chars_hi, $chars_lo);
}

//This makes an array with each word in numbered sequence, starting with 1.
//It makes changes by reference
function arrayMaker(&$arr, $fh) {
	
	//initializes the counter used
	$tracker = 1;
	
	//initializes the text line
	$newText = "";
	
	//cycles through a file
	while(!feof($fh)){
		
		//pulls the line
		$newText = fgets($fh);
		
		//creates the array entry
		$arr[$tracker] = trim($newText);
		
		//increments the counter to be used as the key
		$tracker++;
	}
	
	//points back at the start of the array
	reset($arr);
}

//This does the same as above, but it also converts the text from UTF-8 to ANSI
function arrayMaker2(&$arr, $fh) {
	$tracker = 1;
	$newText = "";
	while(!feof($fh)){
		$newText = fgets($fh);
		$newText = iconv("UTF-8", "WINDOWS-1252//TRANSLIT", $newText);
		$arr[$tracker] = trim($newText);
		$tracker++;
	}
	reset($arr);
}

//This returns the key of the last element in an array without changing where the array is currently being pointed at.
function endKey( $array ) {
	
	//finds the last element (by moving the pointer)
	end( $array );
	
	//returns the key, we did not pass by reference to the pointer should still be in the same place it was before this function executed
	return key( $array );
}

//This function should split up the chunks made by TreeTagger.  The changes are made by reference.
//It also puts the words for each file in a different element in the parent array.
function blowChunks(&$arr) {
	
	//creates a temporary array to work with
	$TempArray = array();
	
	//this will be used to track line number
	$tracker = 1;
	
	//used to identify when we have hit a new interview
	$newFile = false;
	
	//used to temporarily hold the contents of each line to work with
	$fineN2 = "";
	
	//loops through each item in an array
	foreach ($arr as $line) {
		
		//finds the start of a new interview
		if (endswith(trim($line),"StartFile")) {
			
			//yes, this is a new interview
			$newFile = true;
			
			//so we are back at word 1
			$tracker = 1;
		}
		
		//executes for the first line of a new interview, which is the name of the file
		elseif ($newFile == true) {
			
			//we pull the string
			$fineN2 = stringToArray($line);
			
			//we get only the name of the file
			$fineN2 = $fineN2[0];
			$fineN2 = substr($fineN2,0,5);
			
			//we add a new array to our array of interviews, with the key as the name of the array
			//this will allow us to tag each word for which interview it occurrs in
			$TempArray[$fineN2] = array();
			
			//we're no longer at the start of a new interview
			$newFile = false;
		}
		
		//executes when we are in the middle of an interview
		else {
			
			//executes when we do not have a chunk created by TreeTagger
			if (count(explode("	", $line)) == 3) {
				
				//adds this item to our array for this interview
				$TempArray[$fineN2][$tracker] = $line;
				
				//increments the position for the word
				$tracker++;
			}
			
			//executes when we have found a chunk indicated with a ~
			elseif (strpos($line, "~") !== false) {
				
				//this is executed once for each word in the chunk
				for ($i = 0; $i <= substr_count($line, "~"); $i++) {
					
					//array of the line (words, POS, chunk with ~
					$mainpart = explode("	", $line);
					
					//separates the words
					$subpart1 = explode(" ", $mainpart[0]);
					
					//separates the chunk
					$subpart2 = explode("~", $mainpart[endKey($mainpart)]);
					
					//adds the items to the array as separate entries
					$TempArray[$fineN2][$tracker] = $subpart1[$i]."	".$mainpart[endKey($mainpart)-1]."	".$subpart2[$i];
					
					//increments the number of the word
					$tracker++;
				}
			}
			
			//Does the same thing for chunks using _ instead of ~
			else {
				for ($i = 0; $i <= substr_count($line, "_"); $i++) {
					$mainpart = explode("	", $line);
					$subpart1 = explode(" ", $mainpart[0]);
					$subpart2 = explode("_", $mainpart[endKey($mainpart)]);
					$TempArray[$fineN2][$tracker] = $subpart1[$i]."	".$mainpart[endKey($mainpart)-1]."	".$subpart2[$i];
					$tracker++;
				}
			}
		}
		
	}
	
	//updates the original array
	$arr = $TempArray;
}

//This cycles through the given array to look for a match and returns the target.
function findMatch($arr, $target, &$start, $diff, $idioma, &$log, $hasAp, $lenDiff) {

	//pulls the character list
	global $aValid;
	
	//this tracks the location in the spanish array, the english array tends to get ahead of itself because it separates compound words
	$startingPoint = $start;
	$start += $diff;
	$end = $start + 50;
	
	//It cycles until it hits the end of the array, which you better hope it doesn't
	//If it does, that usually means that there is a difference between the tagged and original files.
	while ($start < endKey($arr) and $start <= $end) {
		
		//This is the array of the next element in the comparison array
		$tester = stringToArray($arr[$start]);
		
		//This is the word in that array
		$subTest = $tester[0];
		
		//This determines whether the word in this array is the same as the word we are looking for
		if ($subTest == $target) {
			
			if ($hasAp == true) { $start++;}
			
			//next time start by looking at the next word
			$start++;
			
			//array of the next element in the array, which is the ending character position of the current word
			$endingS = stringToArray($arr[$start]);
			
			//this is that position
			$endingC = $endingS[0] - $lenDiff;
			
			//this is the calculated character position of the first letter of the current word
			$startC = $endingC - strlen($target) + 1;
			
			//this is the array for the next line, which is the speaker
			$speakerS = stringToArray($arr[$start+1]);
			
			//this is that speaker
			$speakerC = $speakerS[0];
			
			//this is the array for the next line, which possibly contains punctuation
			$nextS = explode("	", $arr[$start+2]);
			
			//this is the contents of that line
			$nextC = $nextS[0];
			
			//this is the POS tag for that line
			$nextZ = $nextS[1];
			
			//this will be a blank slot for punctuation
			$punc = '';
						
			//executes if we do not have punctuation
			if (!in_array($nextZ, $aValid)) {
				
				//we need to skip the lines that had the ending character position and the speaker's id
				$start += 1;
			}
			
			//executes if we do have punctuation
			elseif ($start+3 < endKey($arr)) {
				
				//this is the punctuation so far
				$punc = $nextC;
				
				//we need to skip the lines that had the ending character position, the speaker's id, and the punctuation
				$start += 2;
				
				//this is an array for the next line, which may contain more punctuation
				$nextW = explode ('	', $arr[$start+1]);
				
				//this is the item for that line
				$nextX = $nextW[0];
				
				//this is the POS tag for that line
				$nextY = $nextW[1];
				
				//executes if we are indeed dealing with a second line of punctuation
				if (in_array($nextY, $aValid)) {
					
					//add this punctuation to the previous punctuation
					$punc = $punc." ".$nextX;
					
					//skip this line in the next pass
					$start += 1;
				}
			}
			
			//this array now contains the target item, the punctuation, the starting character, the ending character, and the speaker's id
			if ($hasAp == true) { $start = $startingPoint;}
			elseif ($idioma == "English") {$start -= $diff;}
			return (array($tester[1], $tester[2], $punc, $startC, $endingC, $speakerC));
		}
		
		if ($start >= $end - 8){
			$log .= "Error in file at around line $start in Treetagger file\n";
			$start = $startingPoint;			
			return (array("Error", "Error", "Error", "Error", "Error", "Error"));
		}
		
		//increment to the next item in the array
		$start++;
	}
}

//This determines if a word is probably English.
function findEnglish($word, $ArrayE) {
	
	//returns true if the word is in the list of the top 5000 most common words in English
	//This list has been modified so that words that are spelled the same in both languages are removed.
	return in_array($word, $ArrayE);
}

//Turns a string into an array by splitting it with any whitespace.
function stringToArray($s) {
	return (preg_split ("/[\s,]+/", $s));
}

//This function determines whether a string ends with a given substring
function endswith($string, $test) {
	
	//this is the length of the string
	$strlen = strlen($string);
	
	//this is the length of the substring
	$testlen = strlen($test);
	
	//if the substring is too long, then it can't be contained
	if ($testlen > $strlen) return false;
	
	//determines whether it is contained if it isn't too long
	return substr_compare($string, $test, -$testlen) === 0;
}

// creates a compressed zip file
function create_zip($files = array(),$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		//add the files
		foreach($valid_files as $file) {
			$zip->addFile($file,$file);
		}

		//close the zip -- done!
		$zip->close();

		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}

function findAr ($word, $neutral, &$newText){
	$found = true;
	//these conditionals seek for patterns in possible word endings, and output tags accordingly
	if (endswith($word, "ase") or endswith($word, "ara") or endswith($word, "iera")){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "ases") or endswith($word, "aras") or endswith($word, "ieras")){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "2nd"));}
	elseif (endswith($word, "ásemos") or endswith($word, "áramos") or endswith($word, "iéramos")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "1st"));}
	elseif (endswith($word, "aseis") or endswith($word, "arais") or endswith($word, "ierais")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd"));}
	elseif (endswith($word, "asen") or endswith($word, "aran") or endswith($word, "ieran")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd or 3rd"));}
	elseif (endswith($word, "ría")){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "rías")){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "ríamos")){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "ríais")){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "rían")){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "aba")){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "abas")){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "ábamos")){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "abais")){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "aban")){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "ré")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "rás")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "rá")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "remos")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "réis")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "rán")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "3rd"));}
	elseif (strlen($word) > strlen($neutral) and (endswith($word, "ame") or endswith($word, "ate") or endswith($word, "ale") or endswith($word, "anos") or endswith($word, "aos") or endswith($word, "avos") or endswith($word, "ales") or endswith($word, "alo") or endswith($word, "alos") or endswith($word, "ala") or endswith($word, "alas") or endswith($word, "áme") or endswith($word, "áte") or endswith($word, "ále") or endswith($word, "ános") or endswith($word, "áos") or endswith($word, "ávos") or endswith($word, "áles") or endswith($word, "álo") or endswith($word, "álos") or endswith($word, "ála") or endswith($word, "álas"))){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strlen($word) > strlen($neutral) and (endswith($word, "enme") or endswith($word, "ente") or endswith($word, "enle") or endswith($word, "ennos") or endswith($word, "enos") or endswith($word, "envos") or endswith($word, "enles") or endswith($word, "enlo") or endswith($word, "enlos") or endswith($word, "enla") or endswith($word, "enlas") or endswith($word, "adte") or endswith($word, "adle") or endswith($word, "adnos") or endswith($word, "ados") or endswith($word, "advos") or endswith($word, "adles") or endswith($word, "adlo") or endswith($word, "adlos") or endswith($word, "adla") or endswith($word, "adlas"))){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (endswith($word, "o") and ! in_array($word, array("anduvo", "dio")) or endswith($word, "oy")){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "as") or endswith($word, "ás")){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "a")){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "amos")){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif ($word == "anduvimos"){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "áis") or $word == "dais"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "an")){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "é") or in_array($word, array("anduve", "di"))){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "ste")){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "ó") or in_array($word, array("anduvo", "dio"))){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "steis")){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "ron")){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "e") or $word == "dé"){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "es")){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "2nd"));}
	elseif (endswith($word, "emos")){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "1st"));}
	elseif (endswith($word, "éis")){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "2nd"));}
	elseif (endswith($word, "en")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "3rd"));}
	elseif (strpos($word, "ar") !== false){
		$newText = array_merge($newText, array("Infinitive"));}
	elseif (strpos($word, "ando") !== false){
		$newText = array_merge($newText, array("Gerund"));}
	elseif (strpos($word, "ado") !== false){
		$newText = array_merge($newText, array("Participle"));}
	elseif (strpos($word, "á") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strpos($word, "ad") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	
	//lets us know if we have an -ar verb with an unexpected ending
	else{
		$found = false;}
	return $found;
}

function findErIr ($word, $neutral, &$newText){
	$found = true;
	if (endswith($word, "ese") or endswith($word, "era")){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "eses") or endswith($word, "eras")){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "2nd"));}
	elseif (endswith($word, "ésemos") or endswith($word, "éramos")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "1st"));}
	elseif (endswith($word, "eseis") or endswith($word, "erais")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd"));}
	elseif (endswith($word, "esen") or endswith($word, "eran")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd or 3rd"));}
	elseif (endswith($word, "ría") and ! in_array($word, array("quería", "ría"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "rías") and ! in_array($word, array("querías", "rías"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "ríamos") and ! in_array($word, array("queríamos", "ríamos"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "ríais") and ! in_array($word, array("queríais", "ríais"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "rían") and ! in_array($word, array("querían", "rían"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "3rd"));}
	elseif ($word == "ría"){
		$newText = array_merge($newText, array("Cond or Pres", "Indi or Subj", "Sing", "1st or 3rd"));}
	elseif ($word =="rías"){
		$newText = array_merge($newText, array("Cond or Pres", "Indi or Subj", "Sing", "2nd"));}
	elseif ($word == "ríamos"){
		$newText = array_merge($newText, array("Cond or Pres", "Indi or Subj", "Plural", "1st"));}
	elseif ($word == "ríais"){
		$newText = array_merge($newText, array("Cond or Pres", "Indi or Subj", "Plural", "2nd"));}
	elseif ($word == "rían"){
		$newText = array_merge($newText, array("Cond or Pres", "Indi or Subj", "Plural", "3rd"));}
	elseif (endswith($word, "ía") or $word == "iba"){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "ías") or $word == "ibas"){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "íamos") or $word == "íbamos"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "íais") or $word == "ibais"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "ían") or $word == "iban"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "ré")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "rás")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "rá")){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "remos")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "réis")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "rán")){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "3rd"));}
	elseif ($word == "di" or (strlen($word) > strlen($neutral) and (endswith($word, "ime") or endswith($word, "ite") or endswith($word, "ile") or endswith($word, "inos") or endswith($word, "ios") or endswith($word, "ivos") or endswith($word, "iles") or endswith($word, "ilo") or endswith($word, "ilos") or endswith($word, "ila") or endswith($word, "ilas") or endswith($word, "íme") or endswith($word, "íte") or endswith($word, "íle") or endswith($word, "ínos") or endswith($word, "íos") or endswith($word, "ívos") or endswith($word, "íles") or endswith($word, "ílo") or endswith($word, "ílos") or endswith($word, "íla") or endswith($word, "ílas") or endswith($word, "eme") or endswith($word, "ete") or endswith($word, "ele") or endswith($word, "enos") or endswith($word, "eos") or endswith($word, "evos") or endswith($word, "eles") or endswith($word, "elo") or endswith($word, "elos") or endswith($word, "ela") or endswith($word, "elas") or endswith($word, "éme") or endswith($word, "éte") or endswith($word, "éle") or endswith($word, "énos") or endswith($word, "éos") or endswith($word, "évos") or endswith($word, "éles") or endswith($word, "élo") or endswith($word, "élos") or endswith($word, "éla") or endswith($word, "élas")))){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strlen($word) > strlen($neutral) and (endswith($word, "idme") or endswith($word, "idte") or endswith($word, "idle") or endswith($word, "idnos") or endswith($word, "idos") or endswith($word, "idvos") or endswith($word, "idles") or endswith($word, "idlo") or endswith($word, "idlos") or endswith($word, "idla") or endswith($word, "idlas") or endswith($word, "anme") or endswith($word, "ante") or endswith($word, "anle") or endswith($word, "annos") or endswith($word, "anos") or endswith($word, "anvos") or endswith($word, "anles") or endswith($word, "anlo") or endswith($word, "anlos") or endswith($word, "anla") or endswith($word, "anlas") or endswith($word, "edte") or endswith($word, "edle") or endswith($word, "ednos") or endswith($word, "edos") or endswith($word, "edvos") or endswith($word, "edles") or endswith($word, "edlo") or endswith($word, "edlos") or endswith($word, "edla") or endswith($word, "edlas"))){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (endswith($word, "o") or in_array($word, array("voy", "sé"))){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "es") or $word == "vas" or endswith($word, "és") or endswith($word, "ís")){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "ste")){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "2nd"));}
	elseif (endswith($word, "í") or endswith($word, "yó") or in_array($word, array("puse", "tuve", "dije", "hice", "pude", "quise", "supe", "traje", "vine", "fui", "vi")) or endswith($word, "je")){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "1st"));}
	elseif (endswith($word, "ió") or in_array($word, array("cayó", "puso", "tuvo", "dijo", "hizo", "fue", "oyó", "pudo", "quiso", "rio", "supo", "trajo", "vino", "vio")) or endswith($word, "jo")){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "e") or $word == "va"){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "3rd"));}
	elseif (endswith($word, "emos") or $word == "vamos"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "éis") or $word == "vaís"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "en") or $word == "van"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "imos") and endswith($neutral, "ir") or in_array($word, array("oímos", "reímos"))){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "imos") and endswith($neutral, "er") or endswith($word, "ímos")){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "1st"));}
	elseif (endswith($word, "steis")){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "2nd"));}
	elseif (endswith($word, "eron")){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "3rd"));}
	elseif (endswith($word, "a")){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "1st or 3rd"));}
	elseif (endswith($word, "as")){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "2nd"));}
	elseif (endswith($word, "amos")){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "1st"));}
	elseif (endswith($word, "áis")){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "2nd"));}
	elseif (endswith($word, "an")){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "3rd"));}
	elseif (strpos($word, "er") !== false or strpos($word, "ir") !== false){
		$newText = array_merge($newText, array("Infinitive"));}
	elseif (strpos($word, "iendo") !== false){
		$newText = array_merge($newText, array("Gerund"));}
	elseif (strpos($word, "ido") !== false){
		$newText = array_merge($newText, array("Participle"));}
	elseif (strpos($word, "í") !== false or strpos($word, "é") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strpos($word, "id") !== false or strpos($word, "ed") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (endswith($word, "imos")){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif ($word == "vámonos"){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "1st"));}
	
	//lets us know if we have an -er or -ir verb with an unexpected ending
	else{
		$found = false;}
	return $found;
}

//creates the paths for the files uploaded
$path2="uploads/".$_FILES['uploadedfile']['name'][0];
$path3="uploads/".$_FILES['uploadedfile']['name'][1];

//saves the files
copy($_FILES['uploadedfile']['tmp_name'][0],$path2);
copy($_FILES['uploadedfile']['tmp_name'][1],$path3);

//variables for accessing the uploaded/saved files
$File2 = "uploads/".$_FILES['uploadedfile']['name'][0];
$File3 = "uploads/".$_FILES['uploadedfile']['name'][1];
/*
//this will be the first new file  (this is a two-stage script)
$File2 = "File2.txt";
$File3 = "File3.txt";*/
$File5 = "uploads/File5.txt";

//these are the files that contains the 5000 most frequent words in English and their derivative forms, separated by word length
$HFEng4 = "HFEng4.txt";
$HFEng5 = "HFEng5.txt";
$HFEng6 = "HFEng6.txt";
$HFEng7 = "HFEng7.txt";
$HFEng8 = "HFEng8.txt";
$HFEng9 = "HFEng9.txt";
$HFEng10 = "HFEng10.txt";

//this is a file used for debugging purposes when running the program online
$Bugger = "uploads/Bugger.txt";
$fb = fopen($Bugger, 'w');

//this is the number of files to be created at the end (single or multiple)
$NFiles = $_POST["NFiles"];

//this is the number of time-tagged files uploaded
$i = count($_FILES['file']['tmp_name']);

//this will be the array of time-tagged files
$Files4 = array();
$Handles4 = array();

//loops through each time-tagged file
for ($n = 1; $n <= $i; $n++) {
	$path="uploads/".$_FILES['file']['name'][$n-1];
	copy($_FILES['file']['tmp_name'][$n-1],$path);
	
	//adds an element to the array with the name of that file as the key
	$Files4[$n] = $_FILES['file']['name'][$n-1];
}
/*
$Files4[1] = "AF044_1986_CT_SU2011_ER.srt";
$i = 1;*/
//opens the english-tagged file handle 
$fh2 = fopen($File2, 'r') or die("can't open File2");

//opens the spanish-tagged file handle
$fh3 = fopen($File3, 'r') or die("can't open File3");

//opens the new file handle
$fh5 = fopen($File5, 'w') or die("can't open File5");

//opens the high-frequency english file handles
$fhE4 = fopen($HFEng4, 'r') or die("can't open HFEng4");
$fhE5 = fopen($HFEng5, 'r') or die("can't open HFEng5");
$fhE6 = fopen($HFEng6, 'r') or die("can't open HFEng6");
$fhE7 = fopen($HFEng7, 'r') or die("can't open HFEng7");
$fhE8 = fopen($HFEng8, 'r') or die("can't open HFEng8");
$fhE9 = fopen($HFEng9, 'r') or die("can't open HFEng9");
$fhE10 = fopen($HFEng10, 'r') or die("can't open HFEng10");

//opens a handle for each time-tagged file
for ($n = 1; $n <= $i; $n++) {
	$Handles4[$n] = fopen("uploads/".$Files4[$n], 'r');
}

//this will be an array of arrays, one for each english-tagged interview
$ArrayOf2s = array();

//this will be an array of arrays, one for each spanish-tagged interview
$ArrayOf3s = array();

//this will be an array of arrays, one for each time-tagged interview
$ArrayOf4s = array();

//these will be the arrays of high-frequency english words
$ArrayEng4 = array();
$ArrayEng5 = array();
$ArrayEng6 = array();
$ArrayEng7 = array();
$ArrayEng8 = array();
$ArrayEng9 = array();
$ArrayEng10 = array();

//this loops through each timed file and its handle and adds it to the array of arrays
//it then closes the file, and changes the name of key to the first 5 characters in the file name
for ($n = 1; $n <= $i; $n++) {
	arrayMaker2($ArrayOf4s[$Files4[$n]], $Handles4[$n]);
	fclose($Handles4[$n]);
	$ArrayOf4s[substr($Files4[$n], 0, 5)] = $ArrayOf4s[$Files4[$n]];
	unset($ArrayOf4s[$Files4[$n]]);
}

//this fills in the arrays for the high-frequency english words
arrayMaker($ArrayEng4, $fhE4);
arrayMaker($ArrayEng5, $fhE5);
arrayMaker($ArrayEng6, $fhE6);
arrayMaker($ArrayEng7, $fhE7);
arrayMaker($ArrayEng8, $fhE8);
arrayMaker($ArrayEng9, $fhE9);
arrayMaker($ArrayEng10, $fhE10);

//close the high-frequency english files
fclose($fhE4);
fclose($fhE5);
fclose($fhE6);
fclose($fhE7);
fclose($fhE8);
fclose($fhE9);
fclose($fhE10);

//this fills in the array for the english-tagged data
arrayMaker($ArrayOf2s, $fh2);

//this fills in the array for the spanish-tagged data
arrayMaker($ArrayOf3s, $fh3);

//close the english-tagged file
fclose($fh2);

//close the spanish-tagged file
fclose($fh3);

//this removes the TreeTagger chunks from the english-tagged data
blowChunks($ArrayOf2s);

//this removes the TreeTagger chunks from the spanish-tagged data
blowChunks($ArrayOf3s);

//Time to combine the treetagger and timed data.

//writing the header to the new file.
fwrite($fh5, "Word Number	Original Word	POS	Lemma	Punctuation	Starting Character	Ending Character	Speaker	Start Time	End Time	SRT Line	Language	File ID\n");

//This loops through each time-tagged interview
foreach($ArrayOf4s as $Name => $Array4) {
	
	//used to track the position in the tagged text
	$fTracker = 1;
	$diff = 0;
	
	//used to track position in srt file
	$srtTracker = 0;
	
	//used to tag for srt line code
	$srtLine = "";
	
	//used to tag for the start time
	$startT = "";
	
	//used to tag for the end time
	$endT = "";
	
	//keeps track of which word in the transcript we are looking at
	$word = 0;
	
	//this is for the time data to be held in
	$timeL = array();
	
	//this will hold the results from the searches through the treetagger files.
	$blob = array();
	
	//loops through each line in the current time-tagged file
	foreach($Array4 as $key => $value) {
		
		//if the line is empty, we are starting a new srt entry
		if (trim($value) == "") {
			$srtTracker = 0;
		}
		
		//if the previous line was empty, then this line has the srt entry number
		elseif ($srtTracker == 0) {
			$srtLine = $value;
			$srtTracker = 1;
		}
		
		//the next line has the start and end time info
		elseif ($srtTracker == 1) {
			$srtTracker = 2;
			$timeL = explode(" ", $value);
			$startT = $timeL[0];
			$endT = $timeL[endKey($timeL)];
		}
		
		//the next line(s) has/have the words.
		elseif ($srtTracker == 2) {
			
			//get rid of non-word stuff
			$value = str_replace($bad, " ", $value);
			$value = preg_replace("/^'|'$/", " ", $value);
			
			//make an array of the words.
			$value = explode (" ", $value);
			
			//look at each word in the line
			foreach($value as $subKey => $target) {
				$target = trim($target);
				
				//make sure we are looking at a word
				if ($target != "") {
					
					//increment the number of the word we are looking at
					$word++;
					
					//write the word number and the original word to the file.
					fwrite ();
					
					//lowercase the word.
					$target = lowercase($target);
					$length = strlen($target);
					if ($length <= 4){ $tarE = $ArrayEng4; }
					elseif($length == 5) {$tarE = $ArrayEng5; }
					elseif($length == 6) {$tarE = $ArrayEng6; }
					elseif($length == 7) {$tarE = $ArrayEng7; }
					elseif($length == 8) {$tarE = $ArrayEng8; }
					elseif($length == 9) {$tarE = $ArrayEng9; }
					else {$tarE = $ArrayEng10; }
						
					//executes if the target is in the 5000 most frequent English words
					if ((strpos($target, "'") === false and findEnglish($target, $tarE)) or $target == "'cause") {
						
						//stores the results from searching in the english data
						$blob = findMatch ($ArrayOf2s[$Name], $target, $fTracker, $diff, "English", $log, false, 0);
						
						//writes the pos, lemma, punctuation, starting character, ending character, speaker, start time, end time, srt line, language, and file id to the new file
						fwrite ($fh5, "$word	$target	".$blob[0].'	'.$blob[1].'	 '.$blob[2].'	'.$blob[3].'	'.$blob[4].'	'.$blob[5].'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
					}
					
					//this writes the data to the file for contracted words, which need to be split
					elseif (strpos($target, "'") !== false) {
						$apPos = strpos($target, "'");
						if (strpos($target, "n't") !== false) { $apPos -= 1; }
						$subTar1 = substr($target, 0, $apPos);
						$subTar2 = substr($target, $apPos);
						$diffLen = strlen($subTar2);
						$blob = findMatch ($ArrayOf2s[$Name], $subTar1, $fTracker, $diff, "English", $log, true, $diffLen);
						fwrite ($fh5, "$word	$subTar1	".$blob[0].'	'.$blob[1].'	 '.$blob[2].'	'.$blob[3].'	'.$blob[4].'	'.$blob[5].'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						$diff ++;
						$blob = findMatch ($ArrayOf2s[$Name], $subTar2, $fTracker, $diff, "English", $log, false, 0);
						fwrite ($fh5, "$word	$subTar2	".$blob[0].'	'.$blob[1].'	 '.$blob[2].'	'.$blob[3].'	'.$blob[4].'	'.$blob[5].'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
					}
					
					//does the same thing, but for the spanish word
					else {
						$blob = findMatch ($ArrayOf3s[$Name], $target, $fTracker, 0, "Spanish", $log, false, 0);
						fwrite ($fh5, "$word	$target	".$blob[0].'	'.$blob[1].'	 '.$blob[2].'	'.$blob[3].'	'.$blob[4].'	'.$blob[5].'	'.$startT.'	'.$endT.'	'.$srtLine.'	Spanish	'.$Name."\n");
					}
				}
			}
		}
	}
}

//close the new file
fclose($fh5);

fclose($fb);

//open the new file back up, it is now the old file (this is the second stage)
$File5 = "uploads/File5.txt";

$fh5 = fopen($File5, 'r') or die("can't open File5");

//this will contain the contents of the old file
$Array5 = array();

//used to identify which element in the array we are on
$tracker = 1;

//used to store the text as we work on it
$newText = "";

//this is the POS information
$what = "";

//this is the word as it occurs in the original transcript
$word = "";

//this is the lemma
$neutral = "";

//loops through each line in the old file
while(!feof($fh5)){
	
	//pulls the line of text
	$newText = fgets($fh5);
	
	//creates an array from that line
	$newText = explode("	", trim($newText));
	
	//if the line only has one element, skip it
	if (count($newText) == 1) { continue; }
	
	//the POS is the seventh element
	$what = $newText[2];
	
	//need it lowercase
	$what = lowercase($what);
	
	//the word is the sixth element
	$word = $newText[1];
	
	//also need it in lowercase
	$word = lowercase($word);
	
	//the lemma is the eighth element and is already in lowercase
	$neutral = $newText[3];
	
	//executes if we are looking at the verb estar, haber or ser
	if (in_array($neutral, array("estar", "haber", "ser"))) {
		
		//each of these conditional statements looks for a form of these verbs, and then outputs the corresponding tags
		if (in_array($word, array("estoy", "he", "soy"))) {
			$newText = array_merge($newText,  array("Pres", "Indi", "Sing", "1st"));}
		elseif (in_array($word, array("estás", "has", "eres", "sos"))){
			$newText = array_merge($newText,  array("Pres", "Indi", "Sing", "2nd"));}
		elseif (in_array($word, array("está", "ha", "hay", "es"))){
			$newText = array_merge($newText,  array("Pres", "Indi", "Sing", "3rd"));}
		elseif (in_array($word, array("estamos", "hemos", "somos"))){
			$newText = array_merge($newText,  array("Pres", "Indi", "Plural", "1st"));}
		elseif (in_array($word, array("estáis", "habéis", "sois"))){
			$newText = array_merge($newText,  array("Pres", "Indi", "Plural", "2nd"));}
		elseif (in_array($word, array("están", "han", "son"))){
			$newText = array_merge($newText,  array("Pres", "Indi", "Plural", "3rd"));}
		elseif (in_array($word, array("estaré", "habré", "seré"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Sing", "1st"));}
		elseif (in_array($word, array("estarás", "habrás", "serás"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Sing", "2nd"));}
		elseif (in_array($word, array("estará", "habrá", "será"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Sing", "3rd"));}
		elseif (in_array($word, array("estaremos", "habremos", "seremos"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Plural", "1st"));}
		elseif (in_array($word, array("estaréis", "habréis", "seréis"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Plural", "2nd"));}
		elseif (in_array($word, array("estarán", "habrán", "serán"))){
			$newText = array_merge($newText,  array("Fut", "Indi", "Plural", "3rd"));}
		elseif (in_array($word, array("estaba", "había", "era"))){
			$newText = array_merge($newText,  array("Imp", "Indi", "Sing", "1st or 3rd"));}
		elseif (in_array($word, array("estabas", "habías", "eras"))){
			$newText = array_merge($newText,  array("Imp", "Indi", "Sing", "2nd"));}
		elseif (in_array($word, array("estábamos", "habíamos", "éramos"))){
			$newText = array_merge($newText,  array("Imp", "Indi", "Plural", "1st"));}
		elseif (in_array($word, array("estabais", "habíais", "erais"))){
			$newText = array_merge($newText,  array("Imp", "Indi", "Plural", "2nd"));}
		elseif (in_array($word, array("estaban", "habían", "eran"))){
			$newText = array_merge($newText,  array("Imp", "Indi", "Plural", "3rd"));}
		elseif (in_array($word, array("estuve", "hube", "fui"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Sing", "1st"));}
		elseif (in_array($word, array("estuviste", "hubiste", "fuiste"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Sing", "2nd"));}
		elseif (in_array($word, array("estuvo", "hubo", "fue"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Sing", "3rd"));}
		elseif (in_array($word, array("estuvimos", "hubimos", "fuimos"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Plural", "1st"));}
		elseif (in_array($word, array("estuvisteis", "hubisteis", "fuisteis"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Plural", "2nd"));}
		elseif (in_array($word, array("estuvieron", "hubieron", "fueron"))){
			$newText = array_merge($newText,  array("Pret", "Indi", "Plural", "3rd"));}
		elseif (in_array($word, array("estaría", "habría", "sería"))){
			$newText = array_merge($newText,  array("Cond", "Indi", "Sing", "1st or 3rd"));}
		elseif (in_array($word, array("estarías", "habrías", "serías"))){
			$newText = array_merge($newText,  array("Cond", "Indi", "Sing", "2nd"));}
		elseif (in_array($word, array("estaríamos", "habríamos", "seríamos"))){
			$newText = array_merge($newText,  array("Cond", "Indi", "Plural", "1st"));}
		elseif (in_array($word, array("estaríais", "habríais", "seríais"))){
			$newText = array_merge($newText,  array("Cond", "Indi", "Plural", "2nd"));}
		elseif (in_array($word, array("estarían", "habrían", "serían"))){
			$newText = array_merge($newText,  array("Cond", "Indi", "Plural", "3rd"));}
		elseif (in_array($word, array("esté", "haya", "sea"))){
			$newText = array_merge($newText,  array("Pres", "Subj", "Sing", "1st or 3rd"));}
		elseif (in_array($word, array("estés", "hayas", "seas"))){
			$newText = array_merge($newText,  array("Pres", "Subj", "Sing", "2nd"));}
		elseif (in_array($word, array("estemos", "hayamos", "seamos"))){
			$newText = array_merge($newText,  array("Pres", "Subj", "Plural", "1st"));}
		elseif (in_array($word, array("estéis", "hayáis", "seáis"))){
			$newText = array_merge($newText,  array("Pres", "Subj", "Plural", "2nd"));}
		elseif (in_array($word, array("estén", "hayan", "sean"))){
			$newText = array_merge($newText,  array("Pres", "Subj", "Plural", "3rd"));}
		elseif (in_array($word, array("estuviera", "estuviese", "hubiera", "hubiese", "fuera", "fuese"))){
			$newText = array_merge($newText,  array("Past", "Subj", "Sing", "1st or 3rd"));}
		elseif (in_array($word, array("estuvieras", "estuvieses", "hubieras", "hubieses", "fueras", "fueses"))){
			$newText = array_merge($newText,  array("Past", "Subj", "Sing", "2nd"));}
		elseif (in_array($word, array("estuviéramos", "estuviésemos", "hubiéramos", "hubiésemos", "fuéramos", "fuésemos"))){
			$newText = array_merge($newText,  array("Past", "Subj", "Plural", "1st"));}
		elseif (in_array($word, array("estuvierais", "estuvieseis", "hubierais", "hubieseis", "fuerais", "fueseis"))){
			$newText = array_merge($newText,  array("Past", "Subj", "Plural", "2nd"));}
		elseif (in_array($word, array("estuvieran", "estuviesen", "hubieran", "hubiesen", "fueran", "fuesen"))){
			$newText = array_merge($newText,  array("Past", "Subj", "Plural", "3rd"));}
		elseif (in_array($word, array("está", "sé"))){
			$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
		elseif (in_array($word, array("estad", "sed"))){
			$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
		elseif (strpos($word, "r") !== false){
			$newText = array_merge($newText, array("Infinitive"));}
		elseif (strpos($word, "ndo") !== false){
			$newText = array_merge($newText, array("Gerund"));}
		elseif (strpos($word, "do") !== false){
			$newText = array_merge($newText, array("Participle"));}
			
		//This executes if we have a form of estar, haber or ser that we didn't recognize	
		else{
			$newText = array_merge($newText,  array("Error"));}}
			
	//executes if we have a conjugated verb
	elseif ($what == "vlfin"){
		
		//executes if we have an -ar verb
		if (endswith($neutral, "ar")) {
			if (findAr($word, $neutral, $newText) === false){
				$newText = array_merge($newText, array("Error"));}}
				
		//executes if we have an -er or -ir verb
		elseif (endswith($neutral, "er") or endswith($neutral, "ir")) {
			if (findErIr($word, $neutral, $newText) === false){
				$newText = array_merge($newText, array("Error"));}}
				
		//lets us know that the neutral form was not identified correctly
		else {
			if (endswith($word, "rme") or endswith($word, "rte") or endswith($word, "rle") or endswith($word, "rnos") or endswith($word, "ros") or endswith($word, "rvos") or endswith($word, "rles") or endswith($word, "rlo") or endswith($word, "rlos") or endswith($word, "rla") or endswith($word, "rlas")){
				$newText = array_merge($newText, array("Infinitive"));}
			elseif (endswith($word, "ndome") or endswith($word, "ndote") or endswith($word, "ndole") or endswith($word, "ndonos") or endswith($word, "ndoos") or endswith($word, "ndovos") or endswith($word, "ndoles") or endswith($word, "ndolo") or endswith($word, "ndolos") or endswith($word, "ndola") or endswith($word, "ndolas")){
				$newText = array_merge($newText, array("Gerund"));}
			elseif (endswith($word, "dome") or endswith($word, "dote") or endswith($word, "dole") or endswith($word, "donos") or endswith($word, "doos") or endswith($word, "dovos") or endswith($word, "doles") or endswith($word, "dolo") or endswith($word, "dolos") or endswith($word, "dola") or endswith($word, "dolas")){
				$newText = array_merge($newText, array("Participle"));}
			elseif (findAr($word, $neutral, $newText) === false and $word != "houston"){
				if (findErIr($word, $neutral, $newText) === false){
					$newText = array_merge($newText, array("Error"));}}}}
				
	//looks at non-verbs
	elseif (in_array($what, array("adj", "art", "card", "dm", "int", "nc", "nmea", "nmon", "np", "ord", "ppc", "ppo", "ppx", "rel"))){
		
		//looks for high-frequency items and patterns to determine appropriate tags.
		if (in_array($neutral, array("foto", "moto", "tele", "madre", "mujer", "merced", "pared", "red", "salud", "sed", "cruz", "faz", "base", "calle", "carne", "clase", "clave", "corriente", "fe", "fiebre", "luz", "nariz", "nuez", "paz", "raíz", "vez", "voz", "filial", "flor", "frase", "fuente", "gente", "leche", "lente", "llave", "mente", "muerte", "nieve", "noche", "imagen", "ley", "mano", "miel", "piel", "sal", "tribu", "nube", "sangre", "sede", "serpiente", "suerte", "tarde", "torre"))){
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Feminine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Feminine"));}}
		elseif (in_array($neutral, array("día", "gorila", "pijama", "sofá", "tranvía", "yoga"))){
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}
		elseif (in_array($neutral, array("se", "le", "nos", "me", "yo", "tú", "vos", "usted","mi", "mí", "os", "su", "tu", "vuestro", "capital", "cólera", "coma", "corte", "cura", "final", "frente", "orden", "papa", "parte", "pendiente", "pez", "amante", "cliente", "guía", "idiota", "modelo", "soprano", "testigo"))){
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Ambiguous"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Ambiguous"));}}
		elseif (endswith($neutral, "ma") or endswith($neutral, "ta") or endswith($neutral, "pa")){
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}
		elseif (endswith($neutral, "a") or endswith($neutral, "dad") or endswith($neutral, "tad") or endswith($neutral, "tud") or endswith($neutral, "ción") or endswith($neutral, "sión") or endswith($neutral, "gión") or endswith($neutral, "ez") or endswith($neutral, "triz") or endswith($neutral, "umbre")){
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Feminine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Feminine"));}}
		else{
			if (endswith($word, "s")){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}}
				
	//updates the contents of the array so that they contain the new tags
	$Array5[$tracker] = $newText;
	
	//increments to the next word
	$tracker++;
}

//closes the old file
fclose($fh5);

//executes if the user wanted all the output in a single file
if ($NFiles == "One") {
	
	//this procedure is exactly the same as that followed above for creating the old file
	$File6 = "uploads/File6.txt";
	$fh6 = fopen($File6, 'w') or die("can't open File6");

	$spot1 = 0;
	$spot2 = 0;
	
	foreach($Array5 as $value3) {
		if ($spot1 == 0) {
			fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", "Word Number	Original Word	POS	Lemma	Punctuation	Starting Character	Ending Character	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n"));
		}
		else {
			foreach($value3 as $key4 => $value4) {
				if ($key4 < count($value3)) {
					fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", $value4)."	");
				}
				$spot2 ++;
			}
			fwrite($fh6, "\n");
		}
		$spot2 = 0;
		$spot1 ++;
	}
	
	fclose($fh6);
}

//executes when the user wanted a separate file for each file
else {
	
	//used to skip the first line of the old file (with the column headings)
	$spot1 = 0;
	
	//used to keep track of the new files.
	$newFiles = array();
	
	//loops through each file
	foreach($Array5 as $value3) {
		
		//skips the old column headings
		if ($spot1 == 0) {
			$spot1++;
		}
		
		//executes if this is the first file
		elseif (!isset($File6)) {
			
			//opens the file
			$File6 = "uploads/".$value3[12].".txt";
			$fh6 = fopen($File6, 'w') or die("can't open File6");
			
			//adds the entry to the list of files
			$newFiles[] = $File6;
			
			//adds the column headings
			fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", "Word Number	Original Word	POS	Lemma	Punctuation	Starting Character	Ending Character	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n"));
			
			//loops through each word and its tags
			foreach($value3 as $key4 => $value4) {
				
				//omits the last (empty) column
				if ($key4 < count($value3)) {
					
					//writes the contents to the file
					fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", $value4)."	");
				}
			}
			
			//adds a new line after each set of tags
			fwrite($fh6, "\n");
		}
		
		//executes if we are starting a new file, but not the first file
		//does the same thing except it also closes the previous file before it does it.
		elseif ($File6 != "uploads/".$value3[12].".txt") {
			fclose($fh6);
			$File6 = "uploads/".$value3[12].".txt";
			$fh6 = fopen($File6, 'w') or die("can't open File6");
			$newFiles[] = $File6;
			fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", "Word Number	Original Word	POS	Lemma	Punctuation	Starting Character	Ending Character	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n"));
			foreach($value3 as $key4 => $value4) {
				if ($key4 < count($value3)) {
					fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", $value4)."	");
				}
			}
			fwrite($fh6, "\n");
		}
		
		//executes if we are in the middle of a file
		//as before, writes each word and its tags to the file
		else {
			foreach($value3 as $key4 => $value4) {
				if ($key4 < count($value3)) {
					fwrite($fh6, iconv("WINDOWS-1252", "UTF-8//TRANSLIT", $value4)."	");
				}
			}
			fwrite($fh6, "\n");
		}
	}
	
	//closes the file
	fclose($fh6);
	
	//creates the file that will be the archive of files
	$File6 = "uploads/File6.zip";
	create_zip($newFiles, $File6, true);
}

//downloads the file or zipped archive to the user's computer
if (file_exists($File6)) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.basename($File6));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($File6));
	ob_clean();
	flush();
	readfile($File6);
	exit;
}

?>