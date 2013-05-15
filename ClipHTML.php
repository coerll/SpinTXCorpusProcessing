<?php
ini_set("auto_detect_line_endings", true);
mb_internal_encoding("UTF-8");
mb_regex_encoding('UTF-8');

$fn = fopen("testHtml.txt", "w");

$pre = array('¿', '¡');
$post = array("?", "!", ".", ",", "...");

$speaker = ">>u";
$pSpeaker = ">>u";
$turn = 1;

$fi = fopen("NewClipData.txt", 'r');
$clip = fgets($fi);
while (!feof($fi)) {
	$line = trim(fgets($fi));
	$stuff = explode("\t", $line);
	$entry = $stuff[1].".txt";
	$fh = fopen("Processing/Tagged/".$entry, 'r') or die("can't open entry");
	$newText = fgets($fh);
	$newText = trim(fgets($fh));
	$contents = explode("	", $newText);
	$turn = 1;
	fwrite ($fn, $contents[0].'	<div id="'.$contents[0].'" class="transcript"><div id="turn-1" class="turn ');
	$speaker = $contents[6];
	if (in_array($contents[2], $pre) !== false) {
		if ($speaker == ">>i") {
			fwrite($fn, 'interviewer"><span class="turn-label">>>i:</span> <span class="turn-int"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span>');
		}
		else {
			fwrite($fn, 'speaker"><span class="turn-label">>>s:</span> <span class="turn-speak"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span>');
		}
		$pSpeaker = $speaker;
	}
	else {
		if ($speaker == ">>i") {
			fwrite($fn, 'interviewer"><span class="turn-label">>>i:</span> <span class="turn-int"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span> ');
		}
		else {
			fwrite($fn, 'speaker"><span class="turn-label">>>s:</span> <span class="turn-speak"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span> ');
		}
		$pSpeaker = $speaker;
	}
	while (!feof($fh)){
		$newText = trim(fgets($fh));
		$contents = explode("	", $newText);
		if ($contents[0] != "") {
			if (in_array($contents[2], $post) !== false) {
				fseek($fn, (ftell($fn) -1));
			}
			if (in_array($contents[2], $pre) !== false) {
				$speaker = $contents[6];
				if ($pSpeaker == $speaker) {
					fwrite($fn, '<span id="'.$contents[1].'" class="token">'.$contents[2].'</span>');
				}
				else {
					$turn += 1;
					fwrite($fn, '</span></div><div id="turn-'.$turn.'" class="turn ');
					if ($speaker == ">>i") {
						fwrite ($fn, 'interviewer"><span class="turn-label">>>i:</span> <span class="turn-int"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span>');
					}
					else {
						fwrite ($fn, 'speaker"><span class="turn-label">>>s:</span> <span class="turn-speak"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span>');
					}
				}
			}
			else {
				$speaker = $contents[6];
				if ($pSpeaker == $speaker) {
						fwrite($fn, '<span id="'.$contents[1].'" class="token">'.$contents[2].'</span> ');
				}
				else {
					$turn += 1;
					fwrite($fn, '</span></div><div id="turn-'.$turn.'" class="turn ');
					if ($speaker == ">>i") {
						fwrite ($fn, 'interviewer"><span class="turn-label">>>i:</span> <span class="turn-int"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span> ');
					}
					else {
						fwrite ($fn, 'speaker"><span class="turn-label">>>s:</span> <span class="turn-speak"><span id="'.$contents[1].'" class="token">'.$contents[2].'</span> ');
					}
				}
			}
		}
		$pSpeaker = $speaker;
	}
	fclose($fh);
	fwrite($fn, "</div></div>\n");
}
fclose($fn);
?>