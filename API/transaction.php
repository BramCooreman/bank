<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an JSON format
 * @param string $query SQL query
 * @return string JSON format 
 */
function print_result($query, $tilinro) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $s = array();
    $s['tilinro'] = $tilinro;
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $s[] = $line;
    }
    echo json_encode($s);
    mysql_free_result($result);
}

/**
 * Prints the result of the query in an JSON format
 * @param string $query SQL query
 * @param string $tempSaldo Temp Saldo that is needed to calculate
 * @param string $tilinro
 * @param string $tempSaldoEnd Temp end saldo
 * @param string $tilinSaldo
 * @return string JSON format 
 */
function print_results($query, $tempSaldo, $tilinro, $tempSaldoEnd, $tilinSaldo) {
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $s = array();
    $s["tempSaldo"] = $tempSaldo;
    $s["tempSaldoEnd"] = $tempSaldoEnd;
    $s["tilinSaldo"] = $tilinSaldo;
    $s["tilinro"] = $tilinro;

    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $s[] = $line;
    }
    echo json_encode($s);
    mysql_free_result($result);
}

/**
 * Gets the last 5 transactions 
 * @param string $ytunnus 
 */
function get_transactions($ytunnus) {
    $query = "SELECT		*
            FROM		TAMK_pankkitili
            WHERE 		ytunnus = '$ytunnus' ";

    $result = mysql_query($query) or mysql_error($query);

    // Tilinumeroa ei löydy (ei pitäisi koskaan tapahtua)
    if (mysql_num_rows($result) == 0) {
        echo "<p>Pankkitiliä ei löydy</p>";
    }
    // Tilinumero löytyi, tallennetaan muuttujaan
    else {
        $row = mysql_fetch_array($result);
        $tilinro = $row['tilinro'];
    }
    $query2 = "	SELECT	tapvm
					, saajanNimi
					, maksajanNimi
					, summa
					, selite
					, viite
					, maksaja
				FROM	TAMK_pankkitapahtuma
				WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
				AND	    tapvm <= now()
				AND		(eiVaikutaSaldoon = ''
						OR eiVaikutaSaldoon IS NULL
						OR eiVaikutaSaldoon = 'l'
						OR eiVaikutaSaldoon = 'a'
						OR eiVaikutaSaldoon = 'k'
						OR eiVaikutaSaldoon = 'm'
						)
					ORDER BY tapvm DESC
					LIMIT 5
					";
    print_result($query2, $tilinro);
}

/**
 * Gets the transactions from dd.mm.yyyy to dd.mm.yyyy
 * @param string $from from date dd.mm.yyyy
 * @param string $to to date dd.mm.yyyy
 * @param string $ytunnus 
 */
function get_transaction($from, $to, $ytunnus) {
    $startDateMySql = date('Y-m-d', strtotime($from));
    $endDateMySql = date('Y-m-d', strtotime($to));
    $query = "SELECT		*
            FROM		TAMK_pankkitili
            WHERE 		ytunnus = '$ytunnus' ";

    $result = mysql_query($query) or mysql_error($query);

    // Tilinumeroa ei löydy (ei pitäisi koskaan tapahtua)
    if (mysql_num_rows($result) == 0) {
        //echo "<p>".localize('Pankkitiliä ei löydy')."</p>";
    }
    // Tilinumero löytyi, tallennetaan muuttujaan
    else {
        $row = mysql_fetch_array($result);
        $tilinro = $row['tilinro'];
    }
    $query2 = "	SELECT	tapvm
                                , saajanNimi
                                , maksajanNimi
                                        , summa
                                        , selite
                                        , viite
                                        , maksaja
                        FROM	TAMK_pankkitapahtuma
                        WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
                        AND		(tapvm >= '$startDateMySql' AND tapvm <= '$endDateMySql')
                        AND		(eiVaikutaSaldoon = ''
                                        OR eiVaikutaSaldoon IS NULL
                                        OR eiVaikutaSaldoon = 'l'
                                        OR eiVaikutaSaldoon = 'a'
                                        OR eiVaikutaSaldoon = 'k'
                                        OR eiVaikutaSaldoon = 'm'
                                        )
                        ORDER BY tapvm ASC
                        ";

    $dateTable = explode(".", $from);

    $yesterday = mktime(0, 0, 0, $dateTable[1], $dateTable[0] - 1, $dateTable[2]);
    $tempSaldo = getSaldo($tilinro, date('Y-m-d', $yesterday));

    if ($tempSaldo >= 0) {
        $tempSaldo = "+" . $tempSaldo;
    }

    $tempSaldoEnd = getSaldo($tilinro, date('Y-m-d', strtotime($to)));
    if ($tempSaldoEnd >= 0) {
        $tempSaldoEnd = "+" . $tempSaldoEnd;
    }

    $tilinSaldo = getSaldo($tilinro, date('Y-m-d'));
    print_results($query2, $tempSaldo, $tilinro, $tempSaldoEnd, $tilinSaldo);
}

$database = databaseConnect();
// Set the content type to text/xml
header("Content-Type: text/xml");

// Check for the path elements
$path = $_SERVER['PATH_INFO'];
if ($path != null) {
    $path_params = explode('/', $path);
    // $path_params = end($parts); 
}

//Did the user post something
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($path_params[1])) {
        $input = file_get_contents("php://input");
        //Get the values and pass it as an array
        $transaction = json_decode($input, true);

        $startDate = $transaction['from'];
        $endDate = $transaction['to'];
        $startDate = checkDateFormat($startDate);
        $endDate = checkDateFormat($endDate);

        if ($startDate != "" && $endDate != "") {
            $re1 = '((?:(?:[0-2]?\\d{1})|(?:[3][01]{1})))(?![\\d])'; # Day 1
            $re2 = '(.)'; # Any Single Character 1
            $re3 = '((?:(?:[0-2]?\\d{1})|(?:[3][01]{1})))(?![\\d])'; # Day 2
            $re4 = '(\\.)'; # Any Single Character 2
            $re5 = '((?:(?:[1]{1}\\d{1}\\d{1}\\d{1})|(?:[2]{1}\\d{3})))(?![\\d])'; # Year 1
            $fromBool = preg_match_all("/" . $re1 . $re2 . $re3 . $re4 . $re5 . "/is", $startDate, $matches);
            $toBool = preg_match_all("/" . $re1 . $re2 . $re3 . $re4 . $re5 . "/is", $endDate, $matches);

            if ($fromBool && $toBool) {
                get_transaction($startDate, $endDate, $path_params[1]);
            }
        }
    }
}//Get the first 5 transactions    
else {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($path_params[1])) {
            defineLang($_GET['lang']);
            get_transactions($path_params[1]);
        }
    }
}
?>
