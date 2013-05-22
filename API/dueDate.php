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
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $s = "<$root_element_name>";
    $s .= "<$wrapper_element_name>";
    if (mysql_num_rows($result) == 0) {
        $s.= "<rowsTotal>0</rowsTotal>";
    } else {
        $s.= "<rowsTotal>-1</rowsTotal>";
        $row = mysql_fetch_array($result);
        $tilinro = $row['tilinro'];
        $s .= "<tilinro>$tilinro</tilinro>";

        $today = date('Y-m-d');

        $query = "	SELECT	tapvm
							, saajanNimi
							, maksajanNimi
							, summa
							, selite
							, maksaja
							, arkistotunnus
					FROM	TAMK_pankkitapahtuma
					WHERE	(maksaja = '$tilinro') 
					AND		(tapvm > '$today') 
					AND		(eiVaikutaSaldoon = ''
								OR eiVaikutaSaldoon IS NULL
								OR eiVaikutaSaldoon = 'l'
								OR eiVaikutaSaldoon = 'a'
								OR eiVaikutaSaldoon = 'k'
								OR eiVaikutaSaldoon = 'm'
							)
					";

        $result = mysql_query($query) or pupe_error($query);
        $s .= "<data>";
        if (mysql_num_rows($result) == 0) {
            $s .= "<rows>0</rows>";
        } else {


            $tilinSaldoAikavalilla = 0;

            // tulostetaan sql-kyselystä saadut tulokset
            $i = 1;
            $s .= "<row>";
            while ($row = mysql_fetch_array($result)) {
                $tapvm = $row['tapvm'];
                $saajanNimi = $row['saajanNimi'];
                $maksajanNimi = $row['maksajanNimi'];
                $summa = $row['summa'];
                $selite = $row['selite'];
                $maksaja = $row['maksaja'];
                $arkistotunnus = $row['arkistotunnus'];



                $s .= "<tapvm>" . date('d.m.Y', strtotime($tapvm)) . "</tapvm>
                                        <saajanNimi>$saajanNimi</saajanNimi>
                                        <maksajanNimi>$maksajanNimi</maksajanNimi>
					";

                // jos laskun maksaja on sama kuin yrityksen oma tili, on kyse maksusta (eli tulostetaan miinusmerkki)
                if ($row['maksaja'] == $tilinro) {
                    $s .= "<summa>-$summa</summa>";
                    $tilinSaldoAikavalilla = $tilinSaldoAikavalilla - $summa;
                } else {
                    $s .= "<summa>+$summa</summa>";
                    $tilinSaldoAikavalilla = $tilinSaldoAikavalilla + $summa;
                }

                $s.= "<selite>$selite</selite>";

                // Erääntyvän laskun poisto
                //$varmistus = localize('Oletko varma että haluat poistaa maksun?');
                $s.= "<arkistotunnus>$arkistotunnus</arkistotunnus>";
            }
            $s .= "</row>";
        }
        $s .= "</data>";
        $s.= "</$wrapper_element_name>";
        $s.= "</$root_element_name>";
        echo $s;
        mysql_free_result($result);
    }
}

/**
 * Get the payments
 * @param string $ytunnus 
 */
function get_payments($ytunnus) {
    $query = "	SELECT		*
				FROM		TAMK_pankkitili
				WHERE 		ytunnus = '$ytunnus'
				";
    $root_element_name = "dueDates";
    $wrapper_element_name = "dueDate";
    print_result($query, $root_element_name, $wrapper_element_name);
}

/**
 * Delete the payments
 * @param string $ytunnus 
 */
function delete_payment($tapahtuma, $ytunnus) {
    $query = "	SELECT		*
				FROM		TAMK_pankkitili
				WHERE 		ytunnus = '$ytunnus'
				";
    $result = mysql_query($query);

    if (mysql_num_rows($result) == 0) {
        echo "<p>" . localize('Pankkitiliä ei löydy') . "</p>";
    } else {
        $row = mysql_fetch_array($result);
        $tilinro = $row['tilinro'];

        // Poistetaan erääntyvä maksu
        $delete = "	DELETE FROM TAMK_pankkitapahtuma
                                        WHERE arkistotunnus = '$tapahtuma'
                                        AND maksaja = '$tilinro'
                                        LIMIT 1";
        mysql_query($delete);
    }
}

$database = databaseConnect();
// Set the content type to text/xml
header("Content-Type: text/xml");

// Check for the path elements
$path = $_SERVER['PATH_INFO'];
if ($path != null) {
    $path_params = explode('/', $path);
}

//Check if the user has posted something
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($path_params[1])) {
        $input = file_get_contents("php://input");

        $xml = simplexml_load_string($input);
        foreach ($xml->dueDate as $dueDate) {
            delete_payment($dueDate->tapahtuma, $path_params[1]);
        }
    }
} else
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($path_params[1])) {
        defineLang($_GET['lang']);
        get_payments($path_params[1]);
    }
}
?>
