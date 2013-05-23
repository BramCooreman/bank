<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an JSON format
 * @param string $query SQL query
 * @return string JSON format 
 */
function print_result($query) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $s = array();

    if (mysql_num_rows($result) == 0) {
        $s['rowsTotal'] = 0;
    } else {
        $s['rowsTotal'] = -1;
        $row = mysql_fetch_array($result);
        $tilinro = $row['tilinro'];
        $s['tilinro'] = $tilinro;

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

        if (mysql_num_rows($result) == 0) {
            $s['rows'] = 0;
        } else {

            // tulostetaan sql-kyselystä saadut tulokset

            while ($row = mysql_fetch_array($result)) {
                //Pass the row straight into the JSON array
                $s[] = $row;
            }
        }

        echo json_encode($s);
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
    print_result($query);
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
        //Get the values of the POST as a JSON array
        $dueDate = json_decode($input, true);
        delete_payment($dueDate['tapahtuma'], $path_params[1]);
    }
} else
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($path_params[1])) {
        defineLang($_GET['lang']);
        get_payments($path_params[1]);
    }
}
?>
