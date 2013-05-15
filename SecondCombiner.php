<?php
/* The purpose of this script is to combine the timing data and TreeTagger data and to tag for additional information.
 * This script is derived from DataCombinerLocal.php and was originally prepared by Arthur Wendorf Spring 2013.
 * Last updated on May 2, 2013.
 */

//These settings will allow the script to run without timing out or encountering Mac-specific errors.
set_time_limit(0);
ini_set("auto_detect_line_endings", true);
ini_set('memory_limit', '-1');
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

//these are all of the POS tags for simplification
$Adjective = array("your", "ADJ", "JJ", "JJR", "JJS", "ORD");
$Adverb = array("ADV", "RB", "RBR", "RBS", "Wh-adverb", "NEG");
$Conjunction = array("CC", "CCAD", "CCNEG", "CQUE", "CSUBF", "CSUBI", "CSUBX");
$Determiner = array("ART", "DM", "CARD", "QU");
$Noun = array("NC", "NMEA", "NMON", "NN", "NNS", "NP", "NPS");
$Other = array("Error", "ACRNM", "ALFP", "ALFS", "CD", "CODE", "DT", "EX", "FO", "FW", "ITJN", "LS", "PDT", "PE", "POS", "RP", "SE", "TO", "UH", "UMMX", "WDT");
$Preposition = array("IN", "PAL", "PDEL", "PREP", "PREP/DEL");
$Pronoun = array("INT", "PP", "PPC", "PPO", "PPX", "REL", "WP", "WP$");
$Punctuation = array("BACKSLASH", "CM", "COLON", "DASH", "DOTS", "FS", "LP", "PERCT", "QT", "RP", "SEMICOLON", "SLASH", "SYM");
$Unknown = array("PNC");
$Verb = array("MD", "VB", "VBD", "VBG", "VBN", "VBP", "VBZ", "VCLIger", "VCLIinf", "VCLIfin", "VEadj", "VEfin", "VEger", "VEinf", "VHadj", "VHfin", "VHger", "VHinf", "VLadj", "VLfin", "VLger", "VLinf", "VMadj", "VMfin", "VMger", "VMinf", "VSadj", "VSfin", "VSger", "VSinf");

//this variable is no longer used but is left in to avoid having to rewrite functions.
$log = "Error log\n";

//This makes an array with each word in numbered sequence, starting with 1.
//It makes changes by reference
function arrayMaker(&$arr, $fh) {
	if ($fh){
	
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
}

//This returns the key of the last element in an array without changing where the array is currently being pointed at.
function endKey( $array ) {
	
	if (is_array($array)) {
	
		//finds the last element (by moving the pointer)
		end( $array );
		
		//returns the key, we did not pass by reference to the pointer should still be in the same place it was before this function executed
		return key( $array );
	}
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
		if (mb_strpos($line,"StartFile", 0, "UTF-8") !== false) {
			
			//yes, this is a new interview
			$newFile = true;
			
			//so we are back at word 1
			$tracker = 1;
		}
		
		//executes for the first line of a new interview, which is the name of the file
		elseif ($newFile == true) {
			
			//we pull the string
			$fineN2 = explode("\t", $line);
			
			//we get only the name of the file
			$fineN2 = $fineN2[0];
			
			//we add a new array to our array of interviews, with the key as the name of the array
			//this will allow us to tag each word for which interview it occurrs in
			$TempArray[$fineN2] = array();
			
			//we're no longer at the start of a new interview
			$newFile = false;
		}
		
		//executes when we are in the middle of an interview
		else {
			
			//executes when we do not have a chunk created by TreeTagger
			if (mb_strpos($line, "_", 0, "UTF-8") === false && mb_strpos($line, "~", 0, "UTF-8") === false && mb_strpos($line, " ", 0, "UTF-8") === false) {
			
				//adds this item to our array for this interview
				$TempArray[$fineN2][$tracker] = $line;
				
				//increments the position for the word
				$tracker++;
			}
			
			//executes when we have found a chunk indicated with a ~
			elseif (mb_strpos($line, "~", 0, "UTF-8") !== false) {
				
				//this is executed once for each word in the chunk
				for ($i = 0; $i <= mb_substr_count($line, "~", "UTF-8"); $i++) {
					
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
			elseif (mb_strpos($line, "_", 0, "UTF-8") !== false) {
				for ($i = 0; $i <= mb_substr_count($line, "_", "UTF-8"); $i++) {
					$mainpart = explode("	", $line);
					$subpart1 = explode(" ", $mainpart[0]);
					$subpart2 = explode("_", $mainpart[endKey($mainpart)]);
					$TempArray[$fineN2][$tracker] = $subpart1[$i]."	".$mainpart[endKey($mainpart)-1]."	".$subpart2[$i];
					$tracker++;
				}
			}
			//Executes for chunks separated by a space.
			else {
				for ($i = 0; $i <= mb_substr_count($line, " ", "UTF-8"); $i++) {
					$mainpart = explode("	", $line);
					$subpart1 = explode(" ", $mainpart[0]);
					$TempArray[$fineN2][$tracker] = $subpart1[$i]."	".$mainpart[endKey($mainpart)-1]."	".$mainpart[-1];
					$tracker++;
				}
			}
		}
		
	}
	
	//updates the original array
	$arr = $TempArray;
}

//This cycles through the given array to look for a match and returns the target.
function findMatch($arr, $target, &$start, &$diff, $idioma, &$log, $hasAp, $lenDiff) {

	//this tracks the location in the spanish array, the english array tends to get ahead of itself because it separates compound words
	$startingPoint = $start;
	if ($idioma == "English"){
		$start += $diff;
	}
	$end = $start + 50;
	
	//It cycles until it hits the end of the array, which you better hope it doesn't
	//If it does, that usually means that there is a difference between the tagged and original files.
	while ($start < endKey($arr) and $start <= $end) {
		
		//This is the array of the next element in the comparison array
		$tester = explode("\t", $arr[$start]);
		
		//This is the word in that array
		$subTest = mb_strtolower($tester[0], "UTF-8");
		
		//This determines whether the word in this array is the same as the word we are looking for
		if ($subTest == $target or ($subTest == "'" and $target == "'s")) {
			
			if ($hasAp == true) { $start++;}
			
			//next time start by looking at the next word
			$start++;
			
			//this array now contains the target item, and the POS
			$sections = count(explode("\t", $subTest));
			$sections2 = count(explode(" ", $subTest));
			$sections3 = $sections + $sections2 - 1;
			if ($hasAp == true or ($idioma == "English" and (mb_strpos($subTest, "~", 0, "UTF-8") or mb_strpos($subTest, "_", 0, "UTF-8") or $sections3 > 3))) {
				$start = $startingPoint;
				$diff--;
			}
			if ($idioma == "English") {$start -= $diff;}
						
			return (array($tester[1], $tester[2]));
		}
		
		if ($start >= $end - 8){
			$log .= "Error in file at around line $start in Treetagger file\n";
			$start = $startingPoint;			
			return (array("Error", "Error"));
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

function findAr ($word, $neutral, &$newText){
	$found = true;
	//these conditionals seek for patterns in possible word endings, and output tags accordingly
	if (mb_ereg("ase$", $word) or mb_ereg("ara$", $word) or mb_ereg("iera$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("ases$", $word) or mb_ereg("aras$", $word) or mb_ereg("ieras$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "2nd"));}
	elseif (mb_ereg("ásemos$", $word) or mb_ereg("áramos$", $word) or mb_ereg("iéramos$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "1st"));}
	elseif (mb_ereg("aseis$", $word) or mb_ereg("arais$", $word) or mb_ereg("ierais$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd"));}
	elseif (mb_ereg("asen$", $word) or mb_ereg("aran$", $word) or mb_ereg("ieran$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd or 3rd"));}
	elseif (mb_ereg("ría$", $word)){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("rías$", $word)){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("ríamos$", $word)){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("ríais$", $word)){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("rían$", $word)){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("aba$", $word)){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("abas$", $word)){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("ábamos$", $word)){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("abais$", $word)){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("aban$", $word)){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("ré$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("rás$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("rá$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("remos$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("réis$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("rán$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "3rd"));}
	elseif (strlen($word) > strlen($neutral) and (mb_ereg("ame$", $word) or mb_ereg("ate$", $word) or mb_ereg("ale$", $word) or mb_ereg("anos$", $word) or mb_ereg("aos$", $word) or mb_ereg("avos$", $word) or mb_ereg("ales$", $word) or mb_ereg("alo$", $word) or mb_ereg("alos$", $word) or mb_ereg("ala$", $word) or mb_ereg("alas$", $word) or mb_ereg("áme$", $word) or mb_ereg("áte$", $word) or mb_ereg("ále$", $word) or mb_ereg("ános$", $word) or mb_ereg("áos$", $word) or mb_ereg("ávos$", $word) or mb_ereg("áles$", $word) or mb_ereg("álo$", $word) or mb_ereg("álos$", $word) or mb_ereg("ála$", $word) or mb_ereg("álas$", $word))){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strlen($word) > strlen($neutral) and (mb_ereg("enme$", $word) or mb_ereg("ente$", $word) or mb_ereg("enle$", $word) or mb_ereg("ennos$", $word) or mb_ereg("enos$", $word) or mb_ereg("envos$", $word) or mb_ereg("enles$", $word) or mb_ereg("enlo$", $word) or mb_ereg("enlos$", $word) or mb_ereg("enla$", $word) or mb_ereg("enlas$", $word) or mb_ereg("adte$", $word) or mb_ereg("adle$", $word) or mb_ereg("adnos$", $word) or mb_ereg("ados$", $word) or mb_ereg("advos$", $word) or mb_ereg("adles$", $word) or mb_ereg("adlo$", $word) or mb_ereg("adlos$", $word) or mb_ereg("adla$", $word) or mb_ereg("adlas$", $word))){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (mb_ereg("o$", $word) and ! in_array($word, array("anduvo", "dio")) or mb_ereg("oy$", $word)){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("as$", $word) or mb_ereg("ás$", $word)){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("a$", $word)){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("amos$", $word)){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif ($word == "anduvimos"){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("áis$", $word) or $word == "dais$"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("an$", $word)){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("é$", $word) or in_array($word, array("anduve", "di"))){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("ste$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("ó$", $word) or in_array($word, array("anduvo", "dio"))){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("steis$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("ron$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("e$", $word) or $word == "dé"){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("es$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "2nd"));}
	elseif (mb_ereg("emos$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "1st"));}
	elseif (mb_ereg("éis$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "2nd"));}
	elseif (mb_ereg("en$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "3rd"));}
	elseif (strpos($word, "ando") !== false){
		$newText = array_merge($newText, array("Gerund"));}
	elseif (strpos($word, "ado") !== false){
		$newText = array_merge($newText, array("Participle"));}
	elseif (strpos($word, "á") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strpos($word, "ad") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (strpos($word, "ar") !== false){
		$newText = array_merge($newText, array("Infinitive"));}
		
	//lets us know if we have an -ar verb with an unexpected ending
	else{
		$found = false;}
	return $found;
}

function findErIr ($word, $neutral, &$newText){
	$found = true;
	if (mb_ereg("ese$", $word) or mb_ereg("era$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("eses$", $word) or mb_ereg("eras$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Sing", "2nd"));}
	elseif (mb_ereg("ésemos$", $word) or mb_ereg("éramos$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "1st"));}
	elseif (mb_ereg("eseis$", $word) or mb_ereg("erais$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd"));}
	elseif (mb_ereg("esen$", $word) or mb_ereg("eran$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "2nd or 3rd"));}
	elseif (mb_ereg("ría$", $word) and ! in_array($word, array("quería", "ría"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("rías$", $word) and ! in_array($word, array("querías", "rías"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("ríamos$", $word) and ! in_array($word, array("queríamos", "ríamos"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("ríais$", $word) and ! in_array($word, array("queríais", "ríais"))){
		$newText = array_merge($newText, array("Cond", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("rían$", $word) and ! in_array($word, array("querían", "rían"))){
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
	elseif (mb_ereg("ía$", $word) or $word == "iba"){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("ías$", $word) or $word == "ibas"){
		$newText = array_merge($newText, array("Imp", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("íamos$", $word) or $word == "íbamos"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("íais$", $word) or $word == "ibais"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("ían$", $word) or $word == "iban"){
		$newText = array_merge($newText, array("Imp", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("ré$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("rás$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("rá$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("remos$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("réis$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("rán$", $word)){
		$newText = array_merge($newText, array("Fut", "Indi", "Plural", "3rd"));}
	elseif ($word == "di" or (strlen($word) > strlen($neutral) and (mb_ereg("ime$", $word) or mb_ereg("ite$", $word) or mb_ereg("ile$", $word) or mb_ereg("inos$", $word) or mb_ereg("ios$", $word) or mb_ereg("ivos$", $word) or mb_ereg("iles$", $word) or mb_ereg("ilo$", $word) or mb_ereg("ilos$", $word) or mb_ereg("ila$", $word) or mb_ereg("ilas$", $word) or mb_ereg("íme$", $word) or mb_ereg("íte$", $word) or mb_ereg("íle$", $word) or mb_ereg("ínos$", $word) or mb_ereg("íos$", $word) or mb_ereg("ívos$", $word) or mb_ereg("íles$", $word) or mb_ereg("ílo$", $word) or mb_ereg("ílos$", $word) or mb_ereg("íla$", $word) or mb_ereg("ílas$", $word) or mb_ereg("eme$", $word) or mb_ereg("ete$", $word) or mb_ereg("ele$", $word) or mb_ereg("enos$", $word) or mb_ereg("eos$", $word) or mb_ereg("evos$", $word, $word) or mb_ereg("eles$", $word) or mb_ereg("elo$", $word) or mb_ereg("elos$", $word) or mb_ereg("ela$", $word) or mb_ereg("elas$", $word) or mb_ereg("éme$", $word) or mb_ereg("éte$", $word) or mb_ereg("éle$", $word) or mb_ereg("énos$", $word) or mb_ereg("éos$", $word) or mb_ereg("évos$", $word) or mb_ereg("éles$", $word) or mb_ereg("élo$", $word) or mb_ereg("élos$", $word) or mb_ereg("éla$", $word) or mb_ereg("élas$", $word)))){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strlen($word) > strlen($neutral) and (mb_ereg("idme$", $word) or mb_ereg("idte$", $word) or mb_ereg("idle$", $word) or mb_ereg("idnos$", $word) or mb_ereg("idos$", $word) or mb_ereg("idvos$", $word) or mb_ereg("idles$", $word) or mb_ereg("idlo$", $word) or mb_ereg("idlos$", $word) or mb_ereg("idla$", $word) or mb_ereg("idlas$", $word) or mb_ereg("anme$", $word) or mb_ereg("ante$", $word) or mb_ereg("anle$", $word) or mb_ereg("annos$", $word) or mb_ereg("anos$", $word) or mb_ereg("anvos$", $word) or mb_ereg("anles$", $word) or mb_ereg("anlo$", $word) or mb_ereg("anlos$", $word) or mb_ereg("anla$", $word) or mb_ereg("anlas$", $word) or mb_ereg("edte$", $word) or mb_ereg("edle$", $word) or mb_ereg("ednos$", $word) or mb_ereg("edos$", $word) or mb_ereg("edvos$", $word) or mb_ereg("edles$", $word) or mb_ereg("edlo$", $word) or mb_ereg("edlos$", $word) or mb_ereg("edla$", $word) or mb_ereg("edlas$", $word))){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (mb_ereg("o$", $word) or in_array($word, array("voy", "sé"))){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("es$", $word) or $word == "vas" or mb_ereg("és", $word) or mb_ereg("ís", $word)){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg("ste$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "2nd"));}
	elseif (mb_ereg('í$', $word) or mb_ereg("yó$", $word) or in_array($word, array("puse", "tuve", "dije", "hice", "pude", "quise", "supe", "traje", "vine", "fui", "vi")) or mb_ereg("je$", $word)){//(strrpos($word, "í") == (strlen($word) - 1)) or mb_ereg("yó") or in_array($word, array("puse", "tuve", "dije", "hice", "pude", "quise", "supe", "traje", "vine", "fui", "vi")) or mb_ereg("je")){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "1st"));}
	elseif (mb_ereg("ó$", $word) or in_array($word, array("cayó", "puso", "tuvo", "dijo", "hizo", "fue", "oyó", "pudo", "quiso", "rio", "supo", "trajo", "vino", "vio")) or mb_ereg("jo$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("e$", $word) or $word == "va"){
		$newText = array_merge($newText, array("Pres", "Indi", "Sing", "3rd"));}
	elseif (mb_ereg("emos$", $word) or $word == "vamos"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("éis$", $word) or $word == "vaís"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("en$", $word) or $word == "van"){
		$newText = array_merge($newText, array("Pres", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("imos$", $word) and mb_ereg($neutral, "ir$", $word) or in_array($word, array("oímos", "reímos"))){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("imos$", $word) and mb_ereg($neutral, "er$", $word) or mb_ereg("ímos$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "1st"));}
	elseif (mb_ereg("steis$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "2nd"));}
	elseif (mb_ereg("eron$", $word)){
		$newText = array_merge($newText, array("Pret", "Indi", "Plural", "3rd"));}
	elseif (mb_ereg("a$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "1st or 3rd"));}
	elseif (mb_ereg("as$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Sing", "2nd"));}
	elseif (mb_ereg("amos$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "1st"));}
	elseif (mb_ereg("áis$", $word)){
		$newText = array_merge($newText, array("Pres", "Subj", "Plural", "2nd"));}
	elseif (mb_ereg("an$", $word)){
		$newText = array_merge($newText, array("Past", "Subj", "Plural", "3rd"));}
	elseif (strpos($word, "iendo") !== false){
		$newText = array_merge($newText, array("Gerund"));}
	elseif (strpos($word, "ido") !== false){
		$newText = array_merge($newText, array("Participle"));}
	elseif (strpos($word, "í") !== false or strpos($word, "é") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Sing", "2nd"));}
	elseif (strpos($word, "id") !== false or strpos($word, "ed") !== false){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "2nd"));}
	elseif (mb_ereg("imos$", $word)){
		$newText = array_merge($newText, array("Pres or Pret", "Indi", "Plural", "1st"));}
	elseif ($word == "vámonos"){
		$newText = array_merge($newText, array("Pres", "Command", "Plural", "1st"));}
	elseif (strpos($word, "er") !== false or strpos($word, "ir") !== false){
		$newText = array_merge($newText, array("Infinitive"));}
	
	//lets us know if we have an -er or -ir verb with an unexpected ending
	else{
		$found = false;}
	return $found;
}

//variables for accessing the uploaded/saved files
$File2 = "Processing/File2.txt";
$File3 = "Processing/File3.txt";

//this will be the first new file  (this is a two-stage script)
$File5 = "Processing/File5.txt";

//these are the files that contains the 5000 most frequent words in English and their derivative forms, separated by word length
$HFEng4 = "HFEng4.txt";
$HFEng5 = "HFEng5.txt";
$HFEng6 = "HFEng6.txt";
$HFEng7 = "HFEng7.txt";
$HFEng8 = "HFEng8.txt";
$HFEng9 = "HFEng9.txt";
$HFEng10 = "HFEng10.txt";
$HFAmbi = "HFEngConflicts.txt";

//this is a file used for debugging purposes when running the program online
$Bugger = "Processing/Bugger.txt";
$fb = fopen($Bugger, 'w');

//This file contains the following information, which is then put into an array:
//Interview ID, Location of Transcripts relative to MainBatchMaker.py, YouTube ID
$fIDs = "SecondInput.txt";

$fhIDs = fopen($fIDs, 'r');

$i = 0;

$FileData = array();
$temporary = "";

while (!feof($fhIDs)){
	$temporary = fgets($fhIDs);
	$FileData[$i] = mb_split("\t", $temporary);
	$i++;
}

//this is the number of time-tagged files
$i--;

//this will be the array of time-tagged files
$Files4 = array();
$Handles4 = array();

//loops through each time-tagged file
for ($n = 1; $n <= $i; $n++) {
	//adds an element to the array with the name of that file as the key
	$Files4[$n] = $FileData[$n][0];
}

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
$fhAmb = fopen($HFAmbi, 'r') or die("can't open HFAmbi");

//opens a handle for each time-tagged file
for ($n = 1; $n <= $i; $n++) {
	if (strlen($Files4[$n]) > 12) {
		$Files4[$n] = trim($Files4[$n]);
		$Handles4[$n] = fopen("Processing/SRT/".$Files4[$n].".txt", 'r');
	}
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
$ArrayAmbi = array();

//this loops through each timed file and its handle and adds it to the array of arrays
//it then closes the file
for ($n = 1; $n <= $i; $n++) {
	arrayMaker($ArrayOf4s[$Files4[$n]], $Handles4[$n]);
	if ($Handles4[$n]){
		fclose($Handles4[$n]);
	}
}

//this fills in the arrays for the high-frequency english words
arrayMaker($ArrayEng4, $fhE4);
arrayMaker($ArrayEng5, $fhE5);
arrayMaker($ArrayEng6, $fhE6);
arrayMaker($ArrayEng7, $fhE7);
arrayMaker($ArrayEng8, $fhE8);
arrayMaker($ArrayEng9, $fhE9);
arrayMaker($ArrayEng10, $fhE10);
arrayMaker($ArrayAmbi, $fhAmb);

//close the high-frequency english files
fclose($fhE4);
fclose($fhE5);
fclose($fhE6);
fclose($fhE7);
fclose($fhE8);
fclose($fhE9);
fclose($fhE10);
fclose($fhAmb);

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
fwrite($fh5, "Word Number	Original Word	POS	Lemma	Speaker	Start Time	End Time	SRT Line	Language	File ID\n");

//This loops through each time-tagged interview
foreach($ArrayOf4s as $Name => $Array4) {
	
	$spoken = ">>u";
	
	$previousL = "";
	$guess = true;
	
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
	
	$srtTracker = 0;
		
	//loops through each line in the current time-tagged file
	foreach($Array4 as $value) {
		
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
			$EngID = false;
			$EngID2 = false;
			$BlownAway = false;
			
			//Identify speaker.
			if (mb_strpos($value, ">")!== false and mb_strpos($value, "i") <= mb_strpos($value, ">") + 3) {
				$spoken = ">>i";
			}
			elseif (mb_strpos($value, ">")!== false and mb_strpos($value, "s") <= mb_strpos($value, ">") + 3) {
				$spoken = ">>s";
			}
			if(mb_strpos($value, "he's")!== false){
				$EngID = true;
			}
			//Remove non-word stuff.
			$value = mb_ereg_replace('>>i', "", $value);
			$value = mb_ereg_replace('>>s', "", $value);
			$value = mb_ereg_replace('>> i', "", $value);
			$value = mb_ereg_replace('>> s', "", $value);
			$value = mb_ereg_replace('>i', "", $value);
			$value = mb_ereg_replace('>s', "", $value);
			$value = mb_ereg_replace('> i', "", $value);
			$value = mb_ereg_replace('> s', "", $value);
			$value = mb_ereg_replace('>>', "", $value);
			$value = mb_ereg_replace('\%', " % ", $value);
			$value = mb_ereg_replace('\.', " . ", $value);
			$value = mb_ereg_replace('([^0-9])([\,])([^0-9])', "\\1 , \\3", $value);
			$value = mb_ereg_replace('([0-9])([\,])([^0-9])', "\\1 , \\3", $value);
			$value = mb_ereg_replace('([\,])$', " ,", $value);
			$value = mb_ereg_replace('\!', " ! ", $value);
			$value = mb_ereg_replace('\?', " ? ", $value);
			$value = mb_ereg_replace('\:', " ", $value);
			$value = mb_ereg_replace('\;', " ; ", $value);
			$value = mb_ereg_replace('\¡', " ¡ ", $value);
			$value = mb_ereg_replace('\¿', " ¿ ", $value);
			$value = mb_ereg_replace('\(', " ( ", $value);
			$value = mb_ereg_replace('\)', " ) ", $value);
			$value = mb_ereg_replace('\[', " [ ", $value);
			$value = mb_ereg_replace('\]', " ] ", $value);
			$value = mb_ereg_replace("([^A-Za-z])(\')", "\\1 ' ", $value);
			$value = mb_ereg_replace("(\')([^A-Za-z])", " ' \\2", $value);
			$value = mb_ereg_replace("([A-Za-z])(\')([A-Za-z])", "\\1 '\\3", $value);
			$value = mb_ereg_replace("\'$", " '", $value);
			$value = mb_ereg_replace("^\'", "' ", $value);
			$value = mb_ereg_replace('\.  \.  \.', "...", $value);
			$value = mb_ereg_replace("(\' )(s )([^A-Za-z])", " 's \\3", $value);
			$value = mb_ereg_replace("\' s$", " 's", $value);
			
			//make an array of the words.
			$value = explode (" ", $value);
			
			//look at each word in the line
			foreach($value as $subKey => $targetO) {
				$targetO = trim($targetO, "\t \n");
				
				//make sure we are looking at a word
				if ($targetO != "") {
					
					//increment the number of the word we are looking at
					$word++;
					
					//lowercase the word.
					$target = mb_convert_case($targetO, MB_CASE_LOWER, "UTF-8");
					$length = mb_strlen($target, "UTF-8");
					if ($length <= 4){ $tarE = $ArrayEng4; }
					elseif($length == 5) {$tarE = $ArrayEng5; }
					elseif($length == 6) {$tarE = $ArrayEng6; }
					elseif($length == 7) {$tarE = $ArrayEng7; }
					elseif($length == 8) {$tarE = $ArrayEng8; }
					elseif($length == 9) {$tarE = $ArrayEng9; }
					else {$tarE = $ArrayEng10; }
					
					//Identifies words that are likely English.
					if ($target == "s") {
						$target = "'s";
						$EngID2 = true;
					}
					if ($BlownAway = true) {
						$fTracker-= 2;
						$BlownAway = false;
					}
					if ($target == "'s") {
						$BlownAway = true;
					}
					
					if ($target == "'") {
						fwrite ($fh5, "$word	'	POS	'	".$spoken.'	 '.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						$fTracker -=2;
						$previousL = "English";
						break;
					}
						
					elseif (mb_strpos($target, "'", 0, "UTF-8") === 0 and $target != "'cause") {//make it just do the dagger and then change target to not have dagger/////
						$blob = findMatch ($ArrayOf2s[$Name], $target, $fTracker, $diff, "English", $log, false, 0);
						$temp55 = mb_substr($targetO, 1, mb_strlen($targetO, "UTF-8"), "UTF-8");
						fwrite ($fh5, "$word	$temp55	".$blob[0].'	'.$blob[1].'	'.$spoken.'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						$fTracker -=2;
						$previousL = "English";
					}
					
					elseif (findEnglish($target, $ArrayAmbi)) {
						$guess = true;
					}

					//executes if the target is in the 5000 most frequent English words
					if (($guess == true and $previousL == "English") or findEnglish($target, $tarE) or $target == "'cause" or $EngID2 == true) {
						
						//stores the results from searching in the english data
						$blob = findMatch ($ArrayOf2s[$Name], $target, $fTracker, $diff, "English", $log, false, 0);
						
						//writes the pos, lemma, punctuation, starting character, ending character, speaker, start time, end time, srt line, language, and file id to the new file
						fwrite ($fh5, "$word	$targetO	".$blob[0].'	'.$blob[1].'	'.$spoken.'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						if ($EngID2 == true) {
							$EngID2 = false;
							$fTracker-=2;
						}
						$guess = false;
					}
					
					//this writes the data to the file for contracted words, which need to be split
					elseif (mb_strpos($target, "'", 0, "UTF-8") !== false) {
						$apPos = mb_strpos($target, "'", 0, "UTF-8");
						if (mb_strpos($target, "n't", 0, "UTF-8") !== false) { $apPos -= 1; }
						$subTar1 = mb_substr($target, 0, $apPos, "UTF-8");
						$subTar1O = mb_substr($targetO, 0, $apPos, "UTF-8");
						$subTar2 = mb_substr($target, $apPos, mb_strlen($targetO, "UTF-8") - $apPos, "UTF-8");
						$subTar2O = mb_substr($targetO, $apPos, mb_strlen($targetO, "UTF-8") - $apPos, "UTF-8");
						$diffLen = mb_strlen($subTar2, "UTF-8");
						$blob = findMatch ($ArrayOf2s[$Name], $subTar1, $fTracker, $diff, "English", $log, true, $diffLen);
						fwrite ($fh5, "$word	$subTar1O	".$blob[0].'	'.$blob[1].'	'.$spoken.'	 '.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						$word++;
						$blob = findMatch ($ArrayOf2s[$Name], $subTar2, $fTracker, $diff, "English", $log, false, 0);
						fwrite ($fh5, "$word	$subTar2O	".$blob[0].'	'.$blob[1].'	'.$spoken.'	'.$startT.'	'.$endT.'	'.$srtLine.'	English	'.$Name."\n");
						$previousL = "English";
					}
					
					//does the same thing, but for the spanish word
					else {
						$blob = findMatch ($ArrayOf3s[$Name], $target, $fTracker, $diff, "Spanish", $log, false, 0);
						fwrite ($fh5, "$word	$targetO	".$blob[0].'	'.$blob[1].'	'.$spoken.'	'.$startT.'	'.$endT.'	'.$srtLine.'	Spanish	'.$Name."\n");
						$previousL = "Spanish";
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
$File5 = "Processing/File5.txt";

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
	$what = mb_convert_case($what, MB_CASE_LOWER, "UTF-8");
	
	//the word is the sixth element
	$word = $newText[1];
	
	//also need it in lowercase
	$word = mb_convert_case($word, MB_CASE_LOWER, "UTF-8");
	
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
			$newText = array_merge($newText,  array("Error4"));}}
			
	//executes if we have a conjugated verb
	elseif ($what == "vlfin" or $what == "vlinf"){
		
		//executes if we have an -ar verb
		if (mb_ereg("ar$", $neutral)) {
			if (findAr($word, $neutral, $newText) === false){
				$newText = array_merge($newText, array("Error3"));}}
				
		//executes if we have an -er or -ir verb
		elseif (mb_ereg("er$", $neutral) or mb_ereg("ir$", $neutral)) {
			if (findErIr($word, $neutral, $newText) === false){
				$newText = array_merge($newText, array("Error2"));}}
				
		//lets us know that the neutral form was not identified correctly
		else {
			if (mb_ereg("rme$", $word) or mb_ereg("rte$", $word) or mb_ereg("rle$", $word) or mb_ereg("rnos$", $word) or mb_ereg("ros$", $word) or mb_ereg("rvos$", $word) or mb_ereg("rles$", $word) or mb_ereg("rlo$", $word) or mb_ereg("rlos$", $word) or mb_ereg("rla$", $word) or mb_ereg("rlas$", $word)){
				$newText = array_merge($newText, array("Infinitive"));}
			elseif (mb_ereg("ndome$", $word) or mb_ereg("ndote$", $word) or mb_ereg("ndole$", $word) or mb_ereg("ndonos$", $word) or mb_ereg("ndoos$", $word) or mb_ereg("ndovos$", $word) or mb_ereg("ndoles$", $word) or mb_ereg("ndolo$", $word) or mb_ereg("ndolos$", $word) or mb_ereg("ndola$", $word) or mb_ereg("ndolas$", $word)){
				$newText = array_merge($newText, array("Gerund"));}
			elseif (mb_ereg("dome$", $word) or mb_ereg("dote$", $word) or mb_ereg("dole$", $word) or mb_ereg("donos$", $word) or mb_ereg("doos$", $word) or mb_ereg("dovos$", $word) or mb_ereg("doles$", $word) or mb_ereg("dolo$", $word) or mb_ereg("dolos$", $word) or mb_ereg("dola$", $word) or mb_ereg("dolas$", $word)){
				$newText = array_merge($newText, array("Participle"));}
			elseif (findAr($word, $neutral, $newText) === false and $word != "houston"){
				if (findErIr($word, $neutral, $newText) === false){
					$newText = array_merge($newText, array("Error1"));}}}}
				
	//looks at non-verbs
	elseif (in_array($what, array("adj", "art", "card", "dm", "int", "nc", "nmea", "nmon", "np", "ord", "ppc", "ppo", "ppx", "rel"))){
		
		//looks for high-frequency items and patterns to determine appropriate tags.
		if (in_array($neutral, array("foto", "moto", "tele", "madre", "mujer", "merced", "pared", "red", "salud", "sed", "cruz", "faz", "base", "calle", "carne", "clase", "clave", "corriente", "fe", "fiebre", "luz", "nariz", "nuez", "paz", "raíz", "vez", "voz", "filial", "flor", "frase", "fuente", "gente", "leche", "lente", "llave", "mente", "muerte", "nieve", "noche", "imagen", "ley", "mano", "miel", "piel", "sal", "tribu", "nube", "sangre", "sede", "serpiente", "suerte", "tarde", "torre"))){
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Feminine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Feminine"));}}
		elseif (in_array($neutral, array("día", "gorila", "pijama", "sofá", "tranvía", "yoga"))){
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}
		elseif (in_array($neutral, array("se", "le", "nos", "me", "yo", "tú", "vos", "usted","mi", "mí", "os", "su", "tu", "vuestro", "capital", "cólera", "coma", "corte", "cura", "final", "frente", "orden", "papa", "parte", "pendiente", "pez", "amante", "cliente", "guía", "idiota", "modelo", "soprano", "testigo"))){
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Ambiguous"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Ambiguous"));}}
		elseif (mb_ereg("ma$", $neutral) or mb_ereg("ta$", $neutral) or mb_ereg("pa$", $neutral)){
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}
		elseif (mb_ereg("a$", $neutral) or mb_ereg("dad$", $neutral) or mb_ereg("tad$", $neutral) or mb_ereg("tud$", $neutral) or mb_ereg("ción$", $neutral) or mb_ereg("sión$", $neutral) or mb_ereg("gión$", $neutral) or mb_ereg("ez$", $neutral) or mb_ereg("triz$", $neutral) or mb_ereg("umbre$", $neutral)){
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Feminine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Feminine"));}}
		else{
			if (mb_ereg("s$", $word)){
				$newText = array_merge($newText, array("", "", "Plural", "", "Masculine"));}
			else{
				$newText = array_merge($newText, array("", "", "Singular", "", "Masculine"));}}}
	else {
		$newText = array_merge($newText, array("", ""));
	}			
	//updates the contents of the array so that they contain the new tags
	$Array5[$tracker] = $newText;
	
	//increments to the next word
	$tracker++;
}

//closes the old file
fclose($fh5);

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
		$File6 = "Processing/Tagged/".$value3[9].".txt";
		$fh6 = fopen($File6, 'w') or die("can't open File6");
		
		//adds the entry to the list of files
		$newFiles[] = $File6;
		
		//adds the column headings
		fwrite($fh6, "Word Number	Original Word	TreeTagger POS	POS	Lemma	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n");
		
		//used to locate the POS
		$x = 0;
		
		//loops through each word and its tags
		foreach($value3 as $key4 => $value4) {
			
			//omits the last (empty) column
			if ($key4 < count($value3)) {
				
				if ($x == 2) {
					//provides the simplified POS tags
					if (in_array($value4, $Adjective)) { fwrite($fh6, $value4."	Adjective	");}
					elseif (in_array($value4, $Adverb)) { fwrite($fh6, $value4."	Adverb	");}
					elseif (in_array($value4, $Determiner)) { fwrite($fh6, $value4."	Determiner	");}
					elseif (in_array($value4, $Conjunction)) { fwrite($fh6, $value4."	Conjunction	");}
					elseif (in_array($value4, $Noun)) { fwrite($fh6, $value4."	Noun	");}
					elseif (in_array($value4, $Other)) { fwrite($fh6, $value4."	Other	");}
					elseif (in_array($value4, $Preposition)) { fwrite($fh6, $value4."	Preposition	");}
					elseif (in_array($value4, $Pronoun)) { fwrite($fh6, $value4."	Pronoun	");}
					elseif (in_array($value4, $Punctuation)) { fwrite($fh6, $value4."	Punctuation	");}
					elseif (in_array($value4, $Unknown)) { fwrite($fh6, $value4."	Unknown	");}
					elseif (in_array($value4, $Verb)) { fwrite($fh6, $value4."	Verb	");}
						
				}
				else {
					//writes the contents to the file
					fwrite($fh6, $value4."	");
				}
				$x++;
			}
		}
		
		//adds a new line after each set of tags
		fwrite($fh6, "\n");
	}
	
	//executes if we are starting a new file, but not the first file
	//does the same thing except it also closes the previous file before it does it.
	elseif ($File6 != "Processing/Tagged/".$value3[9].".txt") {
		fclose($fh6);
		$File6 = "Processing/Tagged/".$value3[9].".txt";
		$fh6 = fopen($File6, 'w') or die("can't open File6");
		$newFiles[] = $File6;
		$x = 0;
		fwrite($fh6, "Word Number	Original Word	TreeTagger POS	POS	Lemma	Speaker	Start Time	End Time	SRT Line	Language	File ID	Tense	Mood	Number	Person	Gender\n");

		foreach($value3 as $key4 => $value4) {
			
			//omits the last (empty) column
			if ($key4 < count($value3)) {
				
				if ($x == 2) {
					//provides the simplified POS tags
					if (in_array($value4, $Adjective)) { fwrite($fh6, $value4."	Adjective	");}
					elseif (in_array($value4, $Adverb)) { fwrite($fh6, $value4."	Adverb	");}
					elseif (in_array($value4, $Determiner)) { fwrite($fh6, $value4."	Determiner	");}
					elseif (in_array($value4, $Conjunction)) { fwrite($fh6, $value4."	Conjunction	");}
					elseif (in_array($value4, $Noun)) { fwrite($fh6, $value4."	Noun	");}
					elseif (in_array($value4, $Other)) { fwrite($fh6, $value4."	Other	");}
					elseif (in_array($value4, $Preposition)) { fwrite($fh6, $value4."	Preposition	");}
					elseif (in_array($value4, $Pronoun)) { fwrite($fh6, $value4."	Pronoun	");}
					elseif (in_array($value4, $Punctuation)) { fwrite($fh6, $value4."	Punctuation	");}
					elseif (in_array($value4, $Unknown)) { fwrite($fh6, $value4."	Unknown	");}
					elseif (in_array($value4, $Verb)) { fwrite($fh6, $value4."	Verb	");}
						
				}
				else {
					//writes the contents to the file
					fwrite($fh6, $value4."	");
				}
				$x++;
			}
		}
		fwrite($fh6, "\n");
	}
	
	//executes if we are in the middle of a file
	//as before, writes each word and its tags to the file
	else {
		$x = 0;
		foreach($value3 as $key4 => $value4) {
			
			//omits the last (empty) column
			if ($key4 < count($value3)) {
				
				if ($x == 2) {
					//provides the simplified POS tags
					if (in_array($value4, $Adjective)) { fwrite($fh6, $value4."	Adjective	");}
					elseif (in_array($value4, $Adverb)) { fwrite($fh6, $value4."	Adverb	");}
					elseif (in_array($value4, $Determiner)) { fwrite($fh6, $value4."	Determiner	");}
					elseif (in_array($value4, $Conjunction)) { fwrite($fh6, $value4."	Conjunction	");}
					elseif (in_array($value4, $Noun)) { fwrite($fh6, $value4."	Noun	");}
					elseif (in_array($value4, $Other)) { fwrite($fh6, $value4."	Other	");}
					elseif (in_array($value4, $Preposition)) { fwrite($fh6, $value4."	Preposition	");}
					elseif (in_array($value4, $Pronoun)) { fwrite($fh6, $value4."	Pronoun	");}
					elseif (in_array($value4, $Punctuation)) { fwrite($fh6, $value4."	Punctuation	");}
					elseif (in_array($value4, $Unknown)) { fwrite($fh6, $value4."	Unknown	");}
					elseif (in_array($value4, $Verb)) { fwrite($fh6, $value4."	Verb	");}
						
				}
				else {
					//writes the contents to the file
					fwrite($fh6, $value4."	");
				}
				$x++;
			}
		}
		fwrite($fh6, "\n");
	}
}

//closes the file
fclose($fh6);
?>