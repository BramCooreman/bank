<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an XML format
 * @param string $query SQL query
 * @param string $root_element_name <p>Parent tag of the XML</p>
 * @param string $wrapper_element_name <p>Child tag of the XML </p>
 * @return string XML format 
 */
function print_result($query, $root_element_name, $wrapper_element_name) {
    try {
        mysql_query("START TRANSACTION");
        $result = mysql_query($query);
        mysql_query("COMMIT");
    } catch (Exception $error) {
        mysql_query("ROLLBACK");
    }
    $s = "";
    if ($result === true) {
        $s = "<$root_element_name>";
        $s.= "<$wrapper_element_name>";
        $s.= "<result>correct</result>";
        $s.= "</$wrapper_element_name>";
        $s.= "</$root_element_name>";
    } else {
        $s = "<$root_element_name>";
        $s.= "<$wrapper_element_name>";
        $s.= "<result>wrong</result>";
        $s.= "</$wrapper_element_name>";
        $s.= "</$root_element_name>";
    }
    echo $s;
}

/**
 * Inserts the values in the DB
 * @param string $saaja Recipient
 * @param string $saajanNimi Name of recipient
 * @param string $summa Sum
 * @param string $tapvm
 * @param string $viite Reference
 * @param string $arkistotunnus Archive number
 * @param string $laatija Author
 */
function insertInDB($saaja, $saajanNimi, $summa, $tapvm, $viite, $arkistotunnus, $laatija) {
    $query =
            "	INSERT INTO TAMK_pankkitapahtuma set 
		yhtio = 'pankk',
		saaja='$saaja',
		saajanNimi = '$saajanNimi',
		maksaja='',
		maksajanNimi = 'Ylläpitäjä',
		summa='$summa',
		tapvm=if('$tapvm' < now(), now(), '$tapvm'),
		kurssi=1,
		valkoodi = 'EUR',
		viite = '$viite',
		selite = 'Ylläpitäjän suorittama rahan siirto',
		arkistotunnus = '$arkistotunnus', 
		laatija='$laatija',
		luontiaika=now()
	";

    print_result($query, "transfers", "transfer");
}

$database = databaseConnect();
// Set the content type to text/xml
header("Content-Type: text/xml");

// Check for the path elements
$path = $_SERVER['PHP_SELF'];
if ($path != null) {
    $parts = explode('/', $path);
    $path_params = end($parts);
}

//Check if the user has Posted something
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents("php://input");

    $xml = simplexml_load_string($input);
    foreach ($xml->transfer as $transfer) {
        $maksupvm = $transfer->maksunErapaiva; /*         * < Maksun päivämäärä */
        $saajanNimi = $transfer->saajanNimi;        /*         * < Maksun saajan nimi */
        $saaja = $transfer->saajanTili;  /*         * < Maksun saajan tilinumero */
        $viite = $transfer->viesti;   /*         * < Maksun viitenumero */
        $summa = $transfer->summa;
        $arkistotunnus = getArchiveReferenceNumber();

        if (empty($saaja)) {
            $errorText = localize('Tilinumero on virheellinen tai tyhjä.');
        }

        // Tarkistetaan, että saajan tilinumero alkaa FI:llä
        if (substr($saaja, 0, 2) == 'FI') {
            $query = "SELECT	omistaja
                                        FROM	TAMK_pankkitili 
                                        WHERE	yhtio = 'pankk'
                                        AND		tilinro = '$saaja' 
                                        ";

            $result = mysql_query($query);

            // Jos tietoja ei löydy, tilinumero on virheellinen
            if (mysql_num_rows($result) == 0) {
                $errorText = localize('Tilinumero on virheellinen.');
            } else {
                if (empty($saajanNimi)) {
                    $row = mysql_fetch_array($result);
                    $saajanNimi = $row['omistaja'];
                }
            }
        }

        $summa = str_replace(',', '.', $summa);
        if ($summa <= 0) {
            $errorText = localize('Syötä maksun summa.');
        }

        $tapvm = $maksupvm;
        $laatija = $_GET['laatija'];

        insertInDB($saaja, $saajanNimi, $summa, $tapvm, $viite, $arkistotunnus, $laatija);
    }
}
?>
