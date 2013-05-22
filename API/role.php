<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an XML format this of the role
 * @param string $query SQL query
 * @param string $root_element_name <p>Parent tag of the XML</p>
 * @param string $wrapper_element_name <p>Child tag of the XML </p>
 * @return string XML format 
 */
function print_result($query, $root_element_name, $wrapper_element_name) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $num_rows = mysql_num_rows($result);
    $s = "<$root_element_name>";
    if ($num_rows != 0) {
        $s.= "<result>correct</result>";
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $s.= "<$wrapper_element_name>";

            foreach ($line as $key => $col_value) {
                $s.= "<$key>$col_value</$key>";
            }

            $s.= "</$wrapper_element_name>";
        }
    } else {
        $s.= "<$wrapper_element_name>";
        $s.= "<result>wrong</result>";
        $s.= "</$wrapper_element_name>";
    }

    $s.= "</$root_element_name>";
    mysql_free_result($result);
    echo $s;
}

/**
 * Prints the result of the query in an XML format this of the user
 * @param string $query SQL query
 * @param string $root_element_name <p>Parent tag of the XML</p>
 * @param string $wrapper_element_name <p>Child tag of the XML </p>
 * @return string XML format 
 */
function print_results($query, $root_element_name, $wrapper_element_name) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $s = "<$root_element_name>";
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $s.= "<$wrapper_element_name>";

        foreach ($line as $key => $col_value) {
            $s.= "<$key>$col_value</$key>";
        }
        $s.= "</$wrapper_element_name>";
    }

    $s.= "</$root_element_name>";
    echo $s;
    mysql_free_result($result);
}

/**
 * Select the role of the user.
 * @param string $user User
 * @param string $role role 
 */
function selectRole($user, $role) {
    $query = "	SELECT		kuka.kuka 		AS 'kuka' 
					, kuka.yhtio 			AS 'yhtio' 
					, yhtio.nimi 			AS 'yhtionNimi' 
					, TAMK_pankkitili.tilinro 	AS 'tilinro'
					, yhtio.ytunnus			AS 'ytunnus'
				FROM		kuka 
				JOIN		yhtio
				ON			kuka.yhtio = yhtio.yhtio 
				JOIN		TAMK_pankkitili
				ON			yhtio.ytunnus = TAMK_pankkitili.ytunnus
				WHERE		kuka.kuka = '$user' 
						AND yhtio.ytunnus = '$role'
				; ";

    print_result($query, 'roles', 'role');
}

/**
 * Get the user.
 * @param string $user User
 */
function getUser($user) {
    $query = "	
		SELECT  kuka.yhtio		AS 'yhtio'
			, yhtio.nimi 		AS 'yhtionNimi'
			, yhtio.ytunnus		AS 'ytunnus'
		FROM kuka
		JOIN		yhtio
		ON		kuka.yhtio = yhtio.yhtio
		WHERE kuka = '$user'
		ORDER BY yhtionNimi ASC
		";
    print_results($query, 'roles', 'role');
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

//did the user make a post?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents("php://input");

    $xml = simplexml_load_string($input);
    foreach ($xml->role as $role) {
        selectRole($_GET['user'], $role->asiakasrooli);
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    defineLang($_GET['lang']);
    if (isset($_GET['user'])) {
        getUser($_GET['user']);
    }
}
?>
