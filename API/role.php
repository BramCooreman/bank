<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an JSON format this of the role
 * @param string $query SQL query
 * @return string JSON format 
 */
function print_result($query ) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $num_rows = mysql_num_rows($result);
    $s = array();
    if ($num_rows != 0) {
        $s["result"] = "correct";
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
           $s[] = $line;
        }
    } else {
        $s["result"] = "wrong";
    }

    mysql_free_result($result);
    echo json_encode( $s);
}

/**
 * Prints the result of the query in an JSON format this of the user
 * @param string $query SQL query
 * @return string JSON format 
 */
function print_results($query) {
  $result = mysql_query($query) or die('Query failed: ' . mysql_error());
   $s =array();
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
                    $s[] = $line ;
    }
    echo json_encode( $s);
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

    print_result($query);
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
    print_results($query);
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

    $role = json_decode($input,true);
        selectRole($_GET['user'], $role['asiakasrooli']);
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    defineLang($_GET['lang']);
    if (isset($_GET['user'])) {
        getUser($_GET['user']);
    }
}
?>
