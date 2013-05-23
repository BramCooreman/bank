<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an JSON format
 * @param string $query SQL query
 * @return string JSON format 
 */
function print_result($query) {
    try {
        mysql_query("START TRANSACTION");
        $result = mysql_query($query);
        mysql_query("COMMIT");
    } catch (Exception $error) {
        mysql_query("ROLLBACK");
    }
    $s = array();
    if ($result === true) {
        $s['result'] = "correct";
    } else {
        $s['result'] = "wrong";
    }
    echo json_encode($s);
}

/**
 * Inserts the values in the DB
 * @param string $saaja Recipient
 * @param string $saajanNimi Name of recipient
 * @param string $maksaja Payer
 * @param string $maksajanNimi Name of the payer
 * @param string $summa Sum
 * @param string $tapvm
 * @param string $viite Reference
 * @param string $viesti Message
 * @param string $arkistotunnus Archive number
 * @param string $laatija Author
 */
function insertInDB($saaja, $saajanNimi, $maksaja, $maksajanNimi, $summa, $tapvm, $viite, $viesti, $arkistotunnus, $laatija) {
    $query =
            "	INSERT INTO TAMK_pankkitapahtuma set 
		yhtio = 'pankk',
		saaja='$saaja',
		saajanNimi = '$saajanNimi',
		maksaja='$maksaja',
		maksajanNimi = '$maksajanNimi',
		summa='$summa',
		tapvm=if('$tapvm' < now(), now(), '$tapvm'),
		kurssi=1,
		valkoodi = 'EUR',
		viite = '$viite',
		selite = '$viesti',
		arkistotunnus = '$arkistotunnus', 
		laatija='$laatija',
		luontiaika=now()
	";

    print_result($query);
}

//initialize the database
$database = databaseConnect();

// Set the content type to text/xml
header("Content-Type: text/xml");
//Check if the user has send a post to the code behind
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //Get the input of the XML data that has been send to the code behind
    $input = file_get_contents("php://input");
    $payment = json_decode($input, true);

    //Get the parameters that are send through URL
    $ytunnus = $_GET['ytunnus'];
    //Is necessary because REST is Stateless so the Language isn't send with the request 
    defineLang($_GET['lang']);

    $maksupvm = $payment["maksupvm"];                   /*     * < Maksun päivämäärä */
    $saajanNimi = $payment["saajanNimi"];               /*     * < Maksun saajan nimi */
    $saaja = $payment["saajanTili"];                    /*     * < Maksun saajan tilinumero */
    $viite = $payment["viite"];                        /*     * < Maksun viitenumero */
    $viesti = $payment["viesti"];                       /*     * < Maksun viesti */
    $summa = $payment["summa"];                       /*     * < Maksun summa */
    $arkistotunnus = getArchiveReferenceNumber();

    $tiliquery = "SELECT tilinro, omistaja FROM TAMK_pankkitili WHERE ytunnus = '$ytunnus'";
    $tiliresult = mysql_query($tiliquery);

    $maksaja = mysql_result($tiliresult, 0, 'tilinro');
    $maksajanNimi = mysql_result($tiliresult, 0, 'omistaja');

    $pankkitili = $saaja;
    require_once '../../pupesoft/inc/pankkitilinoikeellisuus.php';
    if (empty($pankkitili)) {
        $errorText = localize('Tilinumero on virheellinen, tyhjä.');
    } else {
        $saaja = $pankkitili;
    }

    // Maksaja ja saaja eivät voi olla sama yhtiö
    // The payer and payee can not be the same company
    if ($saaja == $maksaja) {
        $errorText = localize('Et voi maksaa omalle tilillesi.');
    }

    // Tarkistetaan, että saajan tilinumero alkaa 9:llä
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
    // Jos maksun suoritus tapahtuu tänään, tarkastetaan onko rahaa tarpeeksi laskun suorittamiseen
    if ($maksupvm == date('Y-m-d')) {
        if ($summa > getSaldo($maksaja, $maksupvm)) {
            // Ei tarpeeksi rahaa
            $errorText = localize('Tilin saldo ei riitÃ¤ maksuun.');
        } else {
            // Tarpeeksi rahaa, ei toimintoja
        }
    }

    $tapvm = $maksupvm;
    $laatija = $_GET['laatija'];

    //insert the correct data in the database
    insertInDB($saaja, $saajanNimi, $maksaja, $maksajanNimi, $summa, $tapvm, $viite, $viesti, $arkistotunnus, $laatija);
}
?>
