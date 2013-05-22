<?php
header('Content-Type: text/html; charset=UTF-8');
/**
* Define the url path for the resources
*/
define('INCLUDE_PATH', '/var/www/html/ainopankki/lang/');

/**
* Define the language using language code based on BCP 47 + RFC 4644,
* http://www.rfc-editor.org/rfc/bcp/bcp47.txt
*
* The language files can be found in directory ‘lang’
*/
define('LANGUAGE', 'en-us');

function localize($phrase) {

	static $translations = NULL;

	if(is_null($translations)) {
		$lang_file = INCLUDE_PATH. LANGUAGE . '.txt';
		
		// WAT?!
		/*if(!file_exists($lang_file)) {
			$lang_file = INCLUDE_PATH .  'ger.txt';
			echo $lang_file . "\n";
		}*/

		$lang_file_content = file_get_contents($lang_file);
		
		$translations = json_decode($lang_file_content, true);
	}
	return $translations[$phrase];
}
?>