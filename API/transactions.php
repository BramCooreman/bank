<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

//Variables
$errorText = "";
$response = "";
$startDate = "";
$endDate = "";
$tilinSaldoAikavalilla = 0;

echo "<h1>" . localize('Tilitapahtumat') . "</h1>";

//Does the user wants to search something?
if (isset($_POST['search'])) {
    $startDate = $_POST['startdate'];
    $endDate = $_POST['enddate'];
    $startDate = checkDateFormat($startDate);
    $endDate = checkDateFormat($endDate);

    if ($startDate == false) {
        $errorText = "<p class='errorMessage'>" . localize('Tarkista alkupäivämäärä.') . "</p>";
    }
    // jos loppupvm ei ole annettu
    if ($endDate == false) {
        $errorText = "<p class='errorMessage'>" . localize('Tarkista loppupäivämäärä.') . "</p>";
    }

    $today = date('d.m.Y');
    // jos alkupvm on tulevaisuudessa
    if (strtotime($startDate) > strtotime($today)) {
        $errorText = "<p class='errorMessage'>" . localize('Tarkista alkupäivämäärä.') . "</p>";
        $startDate = false;
    }
    // jos lopupvm on tulevaisuudessa
    if (strtotime($endDate) > strtotime($today)) {
        $errorText = "<p class='errorMessage'>" . localize('Tarkista loppupäivämäärä.') . "</p>";
        $endDate = false;
    }

    if ($errorText) {
        print $errorText;
    }

    if ($startDate != false and $endDate != false and empty($errorText)) {
        //HTTPS url to the code behind where the data is processed.
        $url = 'https://localhost/bank/API/transaction.php/' . $_SESSION['ytunnus'];

        //Pass the data that the user has entered
        $data = json_encode(array("from" => $_POST['startdate'], 'to' => $_POST['enddate']));

        //Initialize curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //SSL verification set to true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        /* 0: Don?t check the common name (CN) attribute
         * 1: Check that the common name attribute at least exists
         * 2: Check that the common name exists and that it matches the host name of the server
         */
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // Certificate is necessary to communicate with the code behind
        curl_setopt($ch, CURLOPT_CAINFO, "C:\\xampp\\apache\\conf\\ssl.crt\\server.crt");
        //Parse the values that are received of the API
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        $tilinro = $response["tilinro"];
        echo "<table class='tilinTiedot'><tr><td>" . localize('Tilinro:') . "</td><td> $tilinro</td></tr>";

        echo "<tr><td>" . localize('Tilitapahtumat aikavälillä') . " </td><td>" . $_POST['startdate'] . " - " . $_POST['enddate'] . "</td></tr></table>";


        echo "<div class='content'>
                <table id='tilioteTable'>
                    <tr>
                            <th>" . localize('Tap.pvm') . "</th>
                            <th>" . localize('Saajan nimi') . "</th>
                            <th>" . localize('Maksajan nimi') . "</th>
                            <th>" . localize('Summa') . "</th>
                            <th>" . localize('Viite selite') . "</th>
                    </tr>
                    <tr>
                            <td></td>
                            <td></td>
                            <td>" . localize('Alkusaldo') . " " . $_POST['startdate'] . "</td>
                            <td class='alignRight'>" . $response["tempSaldo"] . "</td><td></td></tr>";

        $i = 1;
        $k = 0;
        foreach ($response as $transaction) {
            if ($k < 4) {
                $k++;
                continue;
            }
            echo "<tr";
            if ($i % 2 == 1)
                echo " class='oddRow'";
            $i++;
            echo "><td>" . date('d.m.Y', strtotime(htmlspecialchars($transaction["tapvm"]))) . "</td>
                                    <td>" . iconv('UTF-8', 'ISO-8859-1', $transaction["saajanNimi"]) . "</td>
                                    <td>" . iconv('UTF-8', 'ISO-8859-1', $transaction["maksajanNimi"]) . "</td>";
            if (htmlspecialchars($transaction["maksaja"]) == $tilinro) {
                echo "<td class='alignRight'>-" . htmlspecialchars($transaction["summa"]) . "</td>";
                $tilinSaldoAikavalilla = $tilinSaldoAikavalilla - htmlspecialchars($transaction["summa"]);
            } else {
                echo "<td class='alignRight'>+" . htmlspecialchars($transaction["summa"]) . "</td>";
                $tilinSaldoAikavalilla = $tilinSaldoAikavalilla + htmlspecialchars($transaction["summa"]);
            }echo "
                                <td>" . iconv('UTF-8', 'ISO-8859-1', $transaction["viite"]) . " " . iconv('UTF-8', 'ISO-8859-1', $transaction["selite"]) . "</td>
                        </tr>
                        ";
        }
        echo "<tr>
                    <td></td>
                    <td></td>
                    <td>" . localize('Tapahtumat yhteensä') . "</td>
                    <td class='alignRight'>" . number_format($tilinSaldoAikavalilla, 2, '.', ' ') . "</td>
                    <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>" . localize('Tilin saldo') . " " . $endDate . "</td>
                <td class='alignRight'>" . $response["tempSaldoEnd"] . "</td>
                <td></td>
            </tr>
        </table>
        <table class='tilinSaldot'>

        <tr><td>" . localize('Tilin saldo') . " " . date('d.m.Y') . ": </td><td>";
        if ($response["tilinSaldo"] >= 0) {
            echo "+";
        }
        echo $response["tilinSaldo"] . localize('euroa') . "</td></tr></table>";
        echo "</div><!-- /tilitapahtumat -->
                    <p><a class='painike' href='index.php?sivu=transactions'>" . localize('Takaisin') . "</a></p>";
    } else {
        if ($startDate == false && $endDate == false) {
            printTimeFrameSearchForm();
        } elseif ($startDate == false) {
            printTimeFrameSearchForm(false, $endDate);
        } elseif ($endDate == false) {
            printTimeFrameSearchForm($startDate);
        }
    }
} else {
    printTimeFrameSearchForm();
}

/**
 * Prints the time search form
 * @param string $startDate Start date
 * @param string $endDate end date
 * @return string time search form
 */
function printTimeFrameSearchForm($startDate = false, $endDate = false) {
    $tilinSaldoAikavalilla = 0;
    print '	<div id="login" class="content">
                        <h2>' . localize('Hae aikaväliltä') . '</h2>
                                <div id="kirjaudulomake">
                                        <form action="" method="post">
                                                <p>' . localize('Syötä tiedot muodossa pp.kk.vvvv') . '</p>
                                                <table id="authentication">
                                                <tr>
                                                <td>' . localize('alkupvm') . '</td>
                                                <td>
                                                <input type="text" name="startdate" id="startdate" value="' . $startDate . '" />
                                                <script type="text/javascript">
                                                        calendar.set("startdate");
                                                </script>
                                                </td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('loppupvm') . '</td>
                                                <td>
                                                <input type="text" name="enddate" id="enddate" value="' . $endDate . '" />
                                                <script type="text/javascript">
                                                        calendar.set("enddate");
                                                </script>
                                                </td>
                                                </tr>
                                                </table>
                                                <p id="painikkeet">
                                                <input type="submit" name="search" value=' . localize('HAE') . ' class="painike"/>
                                                <input type="reset" value=' . localize('TYHJENNÄ') . ' class="painike"/>
                                                </p>

                                        </form>
                                </div><!-- /kirjaudu -->
                        </div><!-- /login -->';

    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/transaction.php/' . $_SESSION['ytunnus'] . '?lang=' . $_SESSION['lang'];

    //Initialize curl
    $client = curl_init($url);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
    //SSL verification set to true
    curl_setopt($client, CURLOPT_SSL_VERIFYPEER, true);
    /* 0: Don?t check the common name (CN) attribute
     * 1: Check that the common name attribute at least exists
     * 2: Check that the common name exists and that it matches the host name of the server
     */
    curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 2);
    // Certificate is necessary to communicate with the code behind
    curl_setopt($client, CURLOPT_CAINFO, "C:\\xampp\\apache\\conf\\ssl.crt\\server.crt");
    $response = json_decode(curl_exec($client), true);
    curl_close($client);

    echo '<div id="tilitapahtumat" class="viimeisetTilit">
            <h2>' . localize("Viimeisimmät tilitapahtumat") . '</h2>
                <table id="tilioteTable" class="tiliTiedot">
                        <tr>
                                <th>' . localize("Tap.pvm") . '</th>
                                <th>' . localize("Saajan nimi") . '</th>
                                <th>' . localize("Maksajan nimi") . '</th>
                                <th>' . localize("Summa") . '</th>
                                <th>' . localize("Viite selite") . '</th>
                        </tr>';

    $i = 1;
    foreach ($response as $transaction) {
        if ($transaction < 1)
            continue;
        $tapvm = htmlspecialchars($transaction['tapvm']);
        $saajanNimi = htmlspecialchars($transaction['saajanNimi']);
        $maksajanNimi = htmlspecialchars($transaction['maksajanNimi']);
        $summa = htmlspecialchars($transaction['summa']);
        $selite = htmlspecialchars($transaction['selite']);
        $viite = htmlspecialchars($transaction['viite']);
        if (!empty($viite)) {
            $viite = $viite . ",<br/>";
        }
        $maksaja = htmlspecialchars($transaction['maksaja']);

        echo "<tr";
        if ($i % 2 == 1)
            echo " class='oddRow'";
        $i++;
        echo ">
                                                <td>" . date('d.m.Y', strtotime($tapvm)) . "</td>
                                                <td>" . iconv('UTF-8', 'ISO-8859-1', $saajanNimi) . "</td>
                                                <td>" . iconv('UTF-8', 'ISO-8859-1', $maksajanNimi) . "</td>
                                        ";

        // jos laskun maksaja on sama kuin yrityksen oma tili, on kyse maksusta (eli tulostetaan miinusmerkki)
        if (htmlspecialchars($transaction['maksaja']) == htmlspecialchars($response['tilinro'])) {
            echo "<td class='alignRight'>-$summa</td>";
            $tilinSaldoAikavalilla = $tilinSaldoAikavalilla - $summa;
        } else {
            echo "<td class='alignRight'>+$summa</td>";
            $tilinSaldoAikavalilla = $tilinSaldoAikavalilla + $summa;
        }

        echo "
                                                <td>" . iconv('UTF-8', 'ISO-8859-1', $viite) . " " . iconv('UTF-8', 'ISO-8859-1', $selite) . "</td>
                                        </tr>
                                        ";
    }

    echo '	
                </table>
            </div><!-- /tilitapahtumat -->';
}

?>
