<?php
/**
 * @file
 * Tärkeitä julkisia funktioita
 *
 * TAMKin oppimisympäristö, Ainopankki
 * functions.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 06.07.2010
 * Modified: 06.10.2010
 *
 */

// Vältetään ongelmia
//chdir('../ainopankki/'); //CHANGING

// TAMK-notifikaatio
include_once 'notification.php';

/**
 * @access public
 *
 * Avataan tietokantayhteys
 */
function databaseConnect() {

	// Otetaan salasanat käyttöön
	require_once dirname(dirname(dirname((__FILE__)))).'/pupesoft/inc/salasanat.php';

	// Luodaan linkki tietokantaan
	$link = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Ongelma ' . mysql_error());
	// Valitaan tietokanta
	$result = mysql_select_db($dbkanta, $link) or die('Ongelmia ' . mysql_error());

	if ($result) {
	} else {
		print 'Ongelma avatessa tietokantaa!';
	}
}

/**
 * Haetaan tilin saldo
 *
 * @access public
 *
 * @param $tilinro 
 *   Tilinumero
 *
 * @param $pvm
 *   Haluttu päivämäärä
 *
 * @return $tilinSaldo
 *  Tilin saldo
 */
function getSaldo($tilinro, $pvm) {
	$query = "	SELECT	sum(if(saaja = '$tilinro', summa, summa * -1))
				FROM	TAMK_pankkitapahtuma
				WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
				AND		tapvm <= '$pvm' 
				AND		(eiVaikutaSaldoon = ''
						OR eiVaikutaSaldoon IS NULL
						OR eiVaikutaSaldoon = 'k'
						OR eiVaikutaSaldoon = 'a'
						OR eiVaikutaSaldoon = 'l'
						OR eiVaikutaSaldoon = 'm'
						)
				";
	$result = mysql_query($query);
	
	$row = mysql_fetch_array($result);
	
	$tilinSaldo = $row[0];
	
	if(!$tilinSaldo) 
		$tilinSaldo=0;
	
	return $tilinSaldo;
}

/**
 * Tarkistetaan syötetty päivämäärä
 *
 * @access public
 *
 * @param $day 
 *   Syötetty päivä
 *
 * @param $month
 *   Syötetty kuukausi
 *
 * @param $year
 *   Syötetty vuosi
 *
 * @return $input
 *   TRUE jos päivä on oikeaa muotoa, FALSE jos ei ole
 */
function checkDateInput($day,$month,$year){
	$input = true;	
	$year = trim($year);
	$month = trim($month);
	$day = trim($day);
	$month31 = array(1,3,5,7,8,10,12);  /**< Kuukaudet, joissa on 31 päivää */
	$month30 = array(4,6,9,11);			/**< Kuukaudet, joissa on 30 päivää */

	if(empty($year) || $year > date('Y')){
		$input = false;
	}
	if(empty($month) || $month > 12){ 
		$input = false;
	}
	if(empty($day)){
		$input = false;
	}
	if(in_array($month, $month31) && $day > 31){
		$input = false;	
	}
	if(in_array($month, $month30) && $day > 30){
		$input = false;
	}
	if($month == 2 && $day > 29){
		$input = false;
	}
	if(($month == 2) && (($year % 4 == 0) || ($year % 100 == 0)) && ($day > 28)){
		$input = false;
	}

	if($input != false){
		$input = sprintf('%04d',$year)."-".sprintf('%02d',$month)."-".sprintf('%02d',$day);
	}
	return $input;
}

/**
 * Palauttaa yhden rivin tietoa HTML formaatissa <tr> jos tietoa on.
 * Returns a single row of data in HTML format <tr> if the information is.
 * @access	public
 * @param	string	$text
 * @param	string	$data
 * @param	boolean $bold		default false, jos true, add class="bold" to second <td>
 * @return	jos tieto on tyhjää, return false
 *			jos tietoa on, return yksi taulukon rivi
 */
function getPossibleTableRow( $text, $data, $bold = false ) {
	$value = false;

	if (!empty( $data )) {
		$value .= '<tr>
					<td>' . $text . '</td>';
		if ($bold === true) {
			$value .= '<td class="bold">';
		} else {
			$value .= '<td>';
		}
		$value .= $data . '</td>
				</tr>'. "\n";
	} 
	
	return $value;
}

/**
 * Tarkastaa onko tieto tyhjää ja palauttaa mahdollisen piilotetun kentän oikealla tiedolla
 * To check whether the data is empty and returns a possible hidden field to the right information
 * @access	public
 * @param	mixed	$data
 * @param	string	$fieldName
 * @return	jos tieto tyhjää return false
 *			jos tietoa on, return yksi piilotettu kenttä oikealla tiedolla
 */
function getPossibleHiddenField( $data, $fieldName ) {
	$value = false;
	
	if (!empty( $data )) {
		$value = "\n" . '<p><input type="hidden" name="' . $fieldName . '" value="' . $data . '"/></p>';
	}
	
	return $value;
}

/**
 * Tarkista päiväyksen muoto, oikea formaatti dd.mm.yyyy
 *
 * @access	public
 * @param	mixed	$data	Päiväys, joka tarkistetaan
 * $return 	Jos päiväys oikeaa muotoa, return true
 *			Jos päiväys ei ole oikeaa muotoa, return false
 */
function checkDateFormat($date){
	if (preg_match ("/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/", $date, $parts)){
		if(checkdate($parts[2],$parts[1],$parts[3])){
			return $date;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
}

/**
 * Tarkistaa, onko tieto numeerista
 *
 * @access	public
 * @param	mixed	$data
 * @return	Jos $data on numeerista, return $data
 * 			Jos $data ei ole numeerista, return false
 */
function isDataNumeric( $data ) {
	$value = false;
	$data = str_replace(",", ".", $data);
	
	if (is_numeric( $data ) and $data >= 0) {
		$value = $data;
	}

	return $value;
}

/**
 * Makes 18 characters long string starting with '41n0P' and ending in 15 characters long random string.
 *
 * @access	public
 * @return	string	$number		18 characters long random number
 */
function getArchiveReferenceNumber() {
	$number = uniqid('41n0P');
	return $number;
}

/**
 * Regenerates session id if it's older than 5 minutes.
 * Destroys session if the site hasn't been refreshed within 15 minutes
 *
 * @access	public
 */
function validateSession(){
	// regenerate session id if it's more than 5 minutes old
	if (!isset($_SESSION['created'])) {
		$_SESSION['created'] = time();
	}
	elseif (time() - $_SESSION['created'] > 300) {
		// session timeout
		session_regenerate_id(true);    // regenerate session ID
		$_SESSION['created'] = time();  // new creation time
	}
	
	if (isset($_SESSION['refreshed']) && ((time() - $_SESSION['refreshed']) > 900)) {
		// session timeout
		session_destroy();   // destroy session data in storage
		session_unset();     // unset $_SESSION variable for the runtime
		header('Location: index.php');
	}
	else{
		$_SESSION['refreshed'] = time();
	}
}

/**
 * Muokkaa yrityksien tilinumerot IBAN-tilinumeroiksi
 *
 * @param $tilinumero
 *   Yrityksen tilinumero
 *
 * @return IBANtilinumero
 *   Valmiiksi muokattu IBAN-tilinumero
 */
function BBANtoIBAN($tilinumero)
{
	$alkuperainen = $tilinumero;
	
	// FI = 1518, kun A = 10, B = 11, ...
	$tilinumero .= "151800";
	
	// Otetaan varmuuden vuoksi itseisarvo
	$abs= gmp_abs($tilinumero);
	
	// String -> Int
	$value = gmp_strval($abs);
	
	// Koska tarkasteltava luku ylittää INT:n salliman raja-arvon, joudutaan käyttämään
	// erillistä GMP-lisäosaa modulaation laskemiseksi
	$jakojaannos = gmp_mod($value, 97);
	
	// IBAN-tilinumeroiden tarkiste lasketaan 98 - jakojäännös
	$tarkiste = 98 - $jakojaannos;
	
	// Jos tarkiste on pienempi kuin 10, lisätään 0 tarkisteen eteen
	if($tarkiste < 10 )
	{
		$tarkiste = "0" . $tarkiste;
	}
	
	// Kasataan IBAN-muotoinen tilinumero kasaan.
	$IBANtilinumero = "FI" . $tarkiste . $alkuperainen;
	
	return $IBANtilinumero;
}

/**
* Define the url path for the resources
*/
define('INCLUDE_PATH', dirname(dirname(__FILE__)).'/lang/');

/**
* Define the language using language code based on BCP 47 + RFC 4644,
* http://www.rfc-editor.org/rfc/bcp/bcp47.txt
*
* The language files can be found in directory ‘lang’
*/
//$_SESSION['lang'] = 'fin';
//if(isset($_SESSION['lang'])){
function defineLang($lang)
{
    if(!defined('LANGUAGE'))
         define('LANGUAGE', $lang);
}

//}
/*
 * Lokaalisaation muodostaminen
 * localization of the formation of
 * @param
 *   Käänneettävä lause / fraasi
 *
 * @return
 *   IF valittu kieli on suomi, return $phrase
 *   IF valittu kieli on englanti, return HTML-ENTITIE -muodossa tekstitiedostosta haettu käännös
 */
 
function localize($phrase) {
	if(LANGUAGE != 'fin') {
		static $translations = NULL;
	
		if(is_null($translations)) {
			$lang_file = INCLUDE_PATH. LANGUAGE . '.txt';
			
			// Ei voida tarkastaa, löytyykö tiedostoa ilmeisimmin safe mode -rajoituksien myötä
			/*if(!file_exists($lang_file)) {
				echo 'EI LÖYDY!' . "\n";
				$lang_file = INCLUDE_PATH .  'fin.txt';
				echo $lang_file . "\n";
			}*/

			$lang_file_content = file_get_contents($lang_file);

			$translations = json_decode($lang_file_content, true);
		}
		return $translations[$phrase];
	}
	
	else {
		// TODO: muokattava kun tietokanta on saatu UTF-8 muotoon
		return mb_convert_encoding($phrase, 'HTML-ENTITIES', 'UTF-8');
	}
}
?>
