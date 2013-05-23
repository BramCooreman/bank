<?php

require_once '../lib/functions.php';

/**
 * Prints the result of the query in a JSON format
 * @param string $query SQL query
 * @param string $password Pass the password that the user has typed
 * @return string JSON format 
 */
function print_result($query, $password) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());

    // Tarkastetaan löytyykö tietoja tietokannasta
    $num_rows = mysql_num_rows($result);
    $s = array();
    // Ei tietoa, ei oikea käyttäjänimi
    if ($num_rows == 0) {
        $s["authenticated"] = 0;
    }
    // Tietoa löytyy, oikea käyttäjänimi
    else {
        $row = mysql_fetch_assoc($result);
        $passwordFromDB = utf8_encode($row['salasana']);

        if ($passwordFromDB == md5($password)) {
            $s["authenticated"] = 1;
            $s["ytunnus"] = $row['ytunnus'];
            $s["yhtionNimi"] = $row['yhtionNimi'];
            $s["kayttaja"] = $row['kuka'];
            $s["tilinro"] = $row['tilinro'];
            $s["profiili"] = $row['profiili'];
        } else {
            $s["authenticated"] = 0;
        }
    }
    echo json_encode($s);
    mysql_free_result($result);
}

/**
 * Identify the user check if the user exists in the DB
 * @param string $username Username
 * @param string $password Password
 */
function identify_user($username, $password) {
    $query = "	SELECT		kuka.kuka 		AS 'kuka' 
                                        , kuka.salasana 	AS 'salasana' 
                                        , yhtio.ytunnus 	AS 'ytunnus' 
                                        , yhtio.nimi 		AS 'yhtionNimi' 
                                        , TAMK_pankkitili.tilinro AS 'tilinro' 
                                        , kuka.profiilit	AS 'profiili'
                        FROM		kuka 
                        JOIN		yhtio
                        ON		kuka.yhtio = yhtio.yhtio 
                        JOIN		TAMK_pankkitili
                        ON		yhtio.ytunnus = TAMK_pankkitili.ytunnus
                        WHERE		kuka.kuka = '$username' 
                        ; ";

    print_result($query, $password);
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

//Did the user pressed Login?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents("php://input");

    //Decode the data that was passed through POST
    $user = json_decode($input, true);
    identify_user($user['username'], $user['password']);
}
?>
