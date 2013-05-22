<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

/**
 * Prints the result of the query in an XML format
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
 * Gets the credit information of the recipient
 * @param string $tilinro Recipient
 */
function get_creditInfo($tilinro) {
    $query = "
                SELECT 	*
                                , DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
                                , SUM(summa) AS summa
                FROM TAMK_pankkitapahtuma 
                WHERE eiVaikutaSaldoon = 'l' 
                        AND yhtio = 'pankk' 
                        AND saaja='$tilinro'
                GROUP BY viite
                ORDER BY tapvm ASC
        ;";
    print_results($query, "creditInfos", "creditInfo");
}

/**
 * Gets the credit informations of the recipient
 * @param string $tilinro Recipient
 * @param string $arkistotunnus Archief nummer
 * @return string XML format
 */
function get_creditInfos($tilinro, $arkistotunnus) {
    $s = "<creditInfos>";
    $s.="<creditInfo>";
    $query = "	SELECT *
							, DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
					FROM TAMK_pankkitapahtuma
					WHERE arkistotunnus = '$arkistotunnus'
					AND saaja = '$tilinro'
				";
    $result = mysql_query($query);
    $row = mysql_fetch_assoc($result);
    //$lainojenMaara = mysql_num_rows; NOT USED
    // Jos löytyy tapahtumia, tarkastetaan lainojen tiedot
    if (mysql_num_rows($result) > 0) {

        $query2 = "	SELECT * 
            FROM TAMK_lainantiedot
            WHERE arkistotunnus = '$arkistotunnus';
            ";
        $result2 = mysql_query($query2);
        $tiedot = mysql_fetch_assoc($result2);

        /**
         * 	Lainojen tiedot
         */
        $koronMaara = $tiedot['korko'] + $tiedot['korkomarginaali'];

        $s .= "<maksaja>" . $row['maksaja'] . "</maksaja>";
        $s .= "<viite>" . $row['viite'] . "</viite>";
        $s .= "<rowDate>" . $row['date'] . "</rowDate>";
        $s .= "<rowSumma>" . $row['viite'] . "</rowSumma>";
        $s .= "<koronMaara>" . $koronMaara . "%</koronMaara>";

        $query = "	SELECT *
                                                    , DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
                                                    , IF(eiVaikutaSaldoon = 'l', summa, summa*-1) AS summa
                                                    , IF(selite REGEXP '[0-9]{2}\/[0-9]{4}$', SUBSTRING(selite, -7), 0) AS eranro
                                    FROM TAMK_pankkitapahtuma
                                    WHERE viite = '$row[viite]'
                                    ORDER BY tapvm ASC, eranro ASC, eiVaikutaSaldoon ASC
                            ";
        $result3 = mysql_query($query);
        $loppusumma = 0;
        $s .= "<data>";

        while ($lyhennys = mysql_fetch_assoc($result3)) {
            $s .= "<lyhennys>";
            if (!empty($lyhennys['selite'])) {
                $s .= "<selite>" . ucfirst($lyhennys['selite']) . "</selite>";
            } else {
                $s .= "<selite>Lainan lyhennys</selite>";
            }

            // Lisätään etumerkki
            if ($lyhennys['summa'] > 0) {
                $etumerkki = "+";
            } else {
                $etumerkki = null;
            }
            $s .= "<date>" . $lyhennys['date'] . "</date>";
            $s .= "<summa>" . $etumerkki . $lyhennys['summa'] . "</summa>";

            // Vähennetään lyhennykset lainan määrästä
            $maksutyyppi = $lyhennys['eiVaikutaSaldoon'];
            if ($maksutyyppi != 'k' && $maksutyyppi != 'm') {
                $loppusumma = $loppusumma + $lyhennys['summa'];
            }
            $s .= "</lyhennys>";
        }

        $s .= "</data>";

        $loppusumma = number_format($loppusumma, 2, '.', '');
        if ($loppusumma >= 0) {
            $loppusumma = "+" . $loppusumma;
        }

        $s .= "<loppusumma>" . $loppusumma . "</loppusumma>";
    } else {
        $s .= "Sinulla ei ole riittäviä oikeuksia tämän luoton tietojen tarkasteluun.";
    }

    $s .= "</creditInfo>";
    $s.="</creditInfos>";
    mysql_free_result($result);
    mysql_free_result($result2);
    mysql_free_result($result3);
    echo $s;
}

/**
 * Show the information of the one recipient that is selected
 * @param string $arkistotunnus Archief nummer
 * @param string $tilinro Recipient
 * @return string XML format
 */
function show_info($arkistotunnus, $tilinro) {
    $yhtsumma = 0;
    $s = "<creditInfos>";
    $s.="<creditInfo>";

    $query = "	SELECT *
                                                      , DATE_FORMAT(tapvm, '%d.%m.%Y') AS date
                                      FROM TAMK_pankkitapahtuma
                                      WHERE arkistotunnus = '$arkistotunnus'
                                      AND saaja = '$tilinro'
                              ";
    $result = mysql_query($query);
    $row = mysql_fetch_assoc($result);
    if (mysql_num_rows($result) > 0) {

        $query2 = "	SELECT * 
                            FROM TAMK_lainantiedot
                            WHERE arkistotunnus = '$arkistotunnus';
                            ";
        $result2 = mysql_query($query2);
        $tiedot = mysql_fetch_assoc($result2);
        $query3 = "
                        SELECT *
                                        , DATE_FORMAT(maksupvm, '%d.%m.%Y') AS erapaiva
                        FROM TAMK_lainatapahtuma
                        WHERE arkistotunnus='$arkistotunnus'
                        AND maksupvm IS NOT NULL
                        ORDER BY maksupvm
                    ";

        $suoritusresult = mysql_query($query3);
        //$suoritusrivit = mysql_num_rows($suoritusresult);
        // muuttujat
        $myonnetty = $row['date'];
        $kokosumma = $row['summa'];
        $era = $tiedot['maksuera'];
        $korkop = ( $tiedot['korko'] + $tiedot['korkomarginaali'] ) / 100;
        $kkkorkop = $korkop / 12;
        if ($era == 0) {
            $erat = 0;
        } else {
            $erat = ceil($kokosumma / $era);
        }
        // tulostetaan jo menneet erät
        $k = 0;
        $s .= "<data>";
        while ($suoritus = mysql_fetch_assoc($suoritusresult)) {
            $s .= "<suoritus>";
            $yhtsumma = $suoritus['lyhennys'] + $suoritus['korko'];

            $k++;
            $kokosumma = $kokosumma - $suoritus['lyhennys'];

            // muotoilut
            $lyhennys = number_format(( $suoritus['lyhennys']), 2, '.', ' ');
            $korko = number_format(($suoritus['korko']), 2, '.', ' ');
            $yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
            $jaljella = number_format(($kokosumma), 2, '.', ' ');

            if ($suoritus['suoritettu'] == 0)
                $s .= "<class>alignRight</class>";
            else
                $s .= "<class>alignRight gray</class>";
            $s.="<number>$k.</number>";
            $s.="<lyhennys>$lyhennys</lyhennys>";
            $s.="<korko>$korko</korko>";
            $s.="<yhtsumma>$yhtsumma</yhtsumma>";
            $s.="<jaljella>$jaljella</jaljella>";
            $s .= "</suoritus>";
        }

        $s .= "</data>";
        // Jos maksettuja eriä ei ollut
        $jaljella = $kokosumma;

        $s .= "<null>";
        if ($lyhennys = '0.00') {
            //echo 'Virheellinen lyhennys (0.00 euroa) joten laina ei lyhenny koskaan' ;

            for ($i = 0; $i < 3; $i++) {
                $s .= "<lyhennys>";
                $k++;

                // lainaa jäljellä ennen maksuerää
                $jaljellaalku = $kokosumma - ($i * $era);

                // kuukauden korko
                $korko = $jaljellaalku * $kkkorkop;

                // tasalyhennys
                if ($tiedot['tyyppi'] == 1) {
                    // lainaa jäljellä maksuerän jälkeen
                    $jaljella = $jaljellaalku - $korko - $era;

                    $yhtsumma = $era + $korko;
                    $lyhennys = $era;
                }
                // kiinteä tasaerä
                if ($tiedot['tyyppi'] == 2) {
                    // lyhennyksen suuruus
                    $lyhennys = $era - $korko;

                    // lainaa jäljellä maksuerän jälkeen
                    $jaljella = $jaljellaalku - $lyhennys;

                    $yhtsumma = $era;
                }

                // Lainaa jäljellä vähemmän kuin maksuerä
                if ($jaljella <= 0) {
                    $yhtsumma = ($yhtsumma + $jaljella);
                    $jaljella = 0;
                }

                $erapaiva = strtotime(date("Y-m-d", strtotime($myonnetty)) . "+$k month");
                $erapaiva = date('d.m.Y', $erapaiva);

                // muotoillaan arvot

                $lyhennys = number_format(($lyhennys), 2, '.', ' ');
                $korko = number_format(($korko), 2, '.', ' ');
                $yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
                $jaljella = number_format(((float) $jaljella), 2, '.', ' ');



                $s.="<number>$k.</number>";
                $s .="<erapaiva>$erapaiva</erapaiva>";
                $s.="<lyhennys>$lyhennys</lyhennys>";
                $s.="<korko>$korko</korko>";
                $s.="<yhtsumma>$yhtsumma</yhtsumma>";
                $s.="<jaljella>$jaljella</jaljella>";

                $s .= "</lyhennys>";
            }
        } else {
            $s .= "<lyhennys>";
            for ($i = 0; $jaljella > 0; $i++) {
                $k++;
                // lainaa jäljellä ennen maksuerää
                $jaljellaalku = $kokosumma - ($i * $era);

                // kuukauden korko
                $korko = $jaljellaalku * $kkkorkop;

                // tasalyhennys
                if ($tiedot['tyyppi'] == 1) {
                    // lainaa jäljellä maksuerän jälkeen
                    $jaljella = $jaljellaalku - $korko - $era;

                    $yhtsumma = $era + $korko;
                    $lyhennys = $era;
                }
                // kiinteä tasaerä
                if ($tiedot['tyyppi'] == 2) {
                    // lyhennyksen suuruus
                    $lyhennys = $era - $korko;

                    // lainaa jäljellä maksuerän jälkeen
                    $jaljella = $jaljellaalku - $lyhennys;

                    $yhtsumma = $era;
                }

                // Lainaa jäljellä vähemmän kuin maksuerä
                if ($jaljella <= 0) {
                    $yhtsumma = ($yhtsumma + $jaljella);
                    $jaljella = 0;
                }

                $erapaiva = strtotime(date("Y-m-d", strtotime($myonnetty)) . "+$k month");
                $erapaiva = date('d.m.Y', $erapaiva);

                // muotoillaan arvot
                $lyhennys = number_format(($lyhennys), 2, '.', ' ');
                $korko = number_format(($korko), 2, '.', ' ');
                $yhtsumma = number_format(($yhtsumma), 2, '.', ' ');
                $jaljella = number_format(($jaljella), 2, '.', ' ');


                $s.="<number>$k.</number>";
                $s .="<erapaiva>$erapaiva</erapaiva>";
                $s.="<lyhennys>$lyhennys</lyhennys>";
                $s.="<korko>$korko</korko>";
                $s.="<yhtsumma>$yhtsumma</yhtsumma>";
                $s.="<jaljella>$jaljella</jaljella>";
                $s .= "</lyhennys>";
            }
        }
        $s .= "</null>";
    }

    $s .= "</creditInfo>";
    $s.="</creditInfos>";
    mysql_free_result($result);
    mysql_free_result($result2);
    mysql_free_result($suoritusresult);
    echo $s;
}

$database = databaseConnect();

// Set the content type to text/xml
header("Content-Type: text/xml");

// Check for the path elements
$path = $_SERVER['PATH_INFO'];
if ($path != null) {
    $path_params = explode('/', $path);
}

//Checks if the users has posted something or
//in this case wants to get the info of one recipients transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($path_params[1]) && isset($path_params[2])) {
        if ($path_params[2] == "references") {
            $input = file_get_contents("php://input");
            $xml = simplexml_load_string($input);
            foreach ($xml->creditInfo as $creditInfo) {
                show_info($creditInfo->ref, $path_params[1]);
            }
        }
    }
} else
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($path_params[1]) && isset($path_params[2]) && isset($path_params[3])) {
        if ($path_params[2] == "references") {
            defineLang($_GET['lang']);
            get_creditInfos($path_params[1], $path_params[3]);
        }
    } else {
        if ($path_params[1] != null && !isset($path_params[2])) {
            defineLang($_GET['lang']);
            get_creditInfo($path_params[1]);
        }
    }
}
?>
