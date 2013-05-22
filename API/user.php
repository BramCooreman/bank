<?php

require_once '../lib/functions.php';

/**
 * Prints the result of the query in an XML format
 * @param string $query SQL query
 * @param string $root_element_name <p>Parent tag of the XML</p>
 * @param string $wrapper_element_name <p>Child tag of the XML </p>
 * @param string $password Pass the password that the user has typed
 * @return string XML format 
 */
function print_result($query, $root_element_name, $wrapper_element_name, $password) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());

    // Tarkastetaan löytyykö tietoja tietokannasta
    $num_rows = mysql_num_rows($result);
    $s = "<$root_element_name>";
    $s.= "<$wrapper_element_name>";
    // Ei tietoa, ei oikea käyttäjänimi
    if ($num_rows == 0) {
        $s .= "<authenticated>0</authenticated>";
    }
    // Tietoa löytyy, oikea käyttäjänimi
    else {
        $row = mysql_fetch_assoc($result);
        $passwordFromDB = utf8_encode($row['salasana']);

        if ($passwordFromDB == md5($password)) {
            $s .= "<authenticated>1</authenticated>";
            $s .= "<ytunnus>" . $row['ytunnus'] . "</ytunnus>";
            $s .= "<yhtionNimi>" . $row['yhtionNimi'] . "</yhtionNimi>";
            $s .= "<kayttaja>" . $row['kuka'] . "</kayttaja>";
            $s .= "<tilinro>" . $row['tilinro'] . "</tilinro>";
            $s .= "<profiili>" . $row['profiili'] . "</profiili>";
        } else {
            $s .= "<authenticated>0</authenticated>";
        }
    }

    $s.= "</$wrapper_element_name>";
    $s.= "</$root_element_name>";
    echo $s;
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

    $root_element_name = 'users';
    $wrapper_element_name = 'user';
    print_result($query, $root_element_name, $wrapper_element_name, $password);
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
    $xml = simplexml_load_string($input);

    foreach ($xml->user as $user) {
        identify_user($user->username, $user->password);
    }
}
?>
