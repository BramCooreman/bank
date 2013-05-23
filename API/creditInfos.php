<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';
echo "<h1>" . localize('Luoton tiedot') . "</h1>";
//Variables
$tilinro = $_SESSION['tilinro'];
$jaljella = 0;
$yhtsumma = 0;
$loppusumma = 0;

//Check if the user wants to see the Repayment Plan 
if (isset($_POST['show'])) {
    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/creditInfo.php/' . $tilinro . "/references";

    //Pass the data that the user has entered
    $data = json_encode(array("ref" => $_GET['ref']));

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
    //Get the values that are send to the user as an array
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    echo "<div class='content marginTop padding20'>";
    echo "<table class='luotontiedot'>";
    echo "<tr class='alignRight'>";
    echo "<th>" . localize('Maksuerä') . "</th>";
    echo "<th>" . localize('Eräpäivä') . "</th>";
    echo "<th>" . localize('Lyhennys') . "</th>";
    echo "<th>" . localize('Korko') . "</th>";
    echo "<th>" . localize('Maksuerä') . "</th>";
    echo "<th>" . localize('Lainaa jäljellä') . "</th>";
    echo "</tr>";

    $k = 0;
    foreach ($response as $key => $creditInfo) {

        if ($creditInfo['lyhennys'] == '0.00') {
            $k++;
            if ($k == 1)
                echo 'Virheellinen lyhennys (0.00 euroa) joten laina ei lyhenny koskaan';
            echo "<tr class='alignRight'>";
            echo "<td>" . htmlspecialchars($creditInfo['number']) . "</td>";
            echo "<td>" . htmlspecialchars($creditInfo['erapaiva']) . "</td>";
            echo "<td>" . htmlspecialchars($creditInfo['lyhennys']) . "&euro;</td>";
            echo "<td>" . htmlspecialchars($creditInfo['korko']) . "&euro;</td>";
            echo "<td>" . htmlspecialchars($creditInfo['yhtsumma']) . "&euro;</td>";
            echo "<td>" . htmlspecialchars($creditInfo['jaljella']) . "&euro;</td>";
            echo "</tr>";
        }else {
            $lyhennys = htmlspecialchars($creditInfo['lyhennys']);
            echo "<tr class='" . ($creditInfo['class']) . "'>";
            echo "<td>" . htmlspecialchars($creditInfo['number']) . "</td>
                    <td>" . htmlspecialchars($creditInfo['erapaiva']) . "</td>
                    <td>" . htmlspecialchars($creditInfo['lyhennys']) . "&euro;</td>
                    <td>" . htmlspecialchars($creditInfo['korko']) . "&euro;</td>
                    <td>" . htmlspecialchars($creditInfo['yhtsumma']) . "&euro;</td>
                    <td>" . htmlspecialchars($creditInfo['jaljella']) . "&euro;</td>
                    ";
        }
    }



    echo "</table>";
    echo "</div>";
    echo "<p><a href='index.php?sivu=creditInfos' class='painike'>" . localize('Takaisin') . "</a></p>";
} else {
    if (isset($_GET['ref'])) {
        //HTTPS url to the code behind where the data is processed.
        $url = 'https://localhost/bank/API/creditInfo.php/' . $tilinro . '/references/' . $_GET['ref'] . '?lang=' . $_SESSION['lang'];

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

        echo "
                    <div class='content padding20'>

                    <p><span class='label'>" . localize('Lainatili:') . "</span> <span class='value'>" . htmlspecialchars($response['maksaja']) . "</span></p>
                    <p><span class='label'>" . localize('Viite:') . "</span> <span class='value'>" . htmlspecialchars($response['viite']) . "</span></p>

                            <table class='luotontiedot'>
                                    <tr class='bold'>
                                            <td>" . localize('Pvm') . "</td>
                                            <td>" . localize('Summa') . "</td>
                                            <td>" . localize('Korko') . "</td>
                                    </tr>
                    ";

        echo "	
                                            <tr>
                                                    <td>" . htmlspecialchars($response['rowDate']) . "</td>
                                                    <td>" . htmlspecialchars($response['rowSumma']) . "</td>
                                                    <td>" . htmlspecialchars($response['koronMaara']) . "</td>
                                            </tr>
                            ";

        echo "</table>
				<form method='post'>
					<p><input class='painike' type='submit' name='show' value=\"" . localize('Takaisinmaksusuunnitelma') . "\" />
                                        </p>
				</form>
                                </div>"; //<a class='painike' href='index.php?sivu=luotonTiedot&amp;ref=".$_GET['ref']."&amp;show=1'>".localize('Takaisinmaksusuunnitelma')."</a>
        echo "	<div class='content marginTop padding20'>
                            <table class='luotontiedot alignLeft'>
                                    <tr>
                                            <th class='selite'>" . localize('Selite') . "</th>
                                            <th>" . localize('Pvm') . "</th>
                                            <th class='alignRight'>" . localize('Summa') . "</th>
                                    </tr>
					";
        $k = 0;
        foreach ($response as $lyhennys => $data) {
            if ($k < 5) {
                $k++;
                continue;
            }

            echo "	<tr>";
            $selite = htmlspecialchars($data['selite']);
            if (!empty($selite)) {
                echo "	<td>" . ucfirst(htmlspecialchars($data['selite'])) . "</td>";
            } else {
                echo "<td>Lainan lyhennys</td>";
            }

            // Lisätään etumerkki
            $summa = $data['summa'];
            if ($summa > 0) {
                $etumerkki = "+";
            } else {
                $etumerkki = null;
            }
            echo "<td>" . htmlspecialchars($data['date']) . "</td>
              <td class='alignRight'>$etumerkki" . htmlspecialchars($data['summa']) . "</td>";

            // Vähennetään lyhennykset lainan määrästä
            $maksutyyppi = $data['eiVaikutaSaldoon'];
            if ($maksutyyppi != 'k' && $maksutyyppi != 'm') {
                $loppusumma = $loppusumma + $summa;
            }

            echo "
                            
                         
                    </tr>";
        }
        $loppusumma = number_format($loppusumma, 2, '.', '');
        if ($loppusumma >= 0) {
            $loppusumma = "+" . $loppusumma;
        }


        echo "			<tr class='height30 verticalBottom borderTop bold'>
                                        <td>" . localize('Lainaa jäljellä') . "</td>
                                        <td>" . date('d.m.Y') . "</td>
                                        <td class='alignRight'>" . $loppusumma . "</td>
								</tr>
							</table>
							
						</div>";
        echo "<p><a href='index.php?sivu=creditInfos' class='painike'>" . localize('Takaisin') . "</a></p>";
    } else {
        $url = 'https://localhost/bank/API/creditInfo.php/' . $tilinro . '?lang=' . $_SESSION['lang'];

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

        echo "	<div class='content padding20'>
                                    <p class='bold'>" . localize('Myönnetyt luotot') . "</p>";

        echo "
                                    <table class='luotontiedot'>
                                            <tr>
                                                    <th>" . localize('Pvm') . "</th>
                                                    <th>" . localize('Summa') . "</th>
                                                    <th>" . localize('Lainatili') . "</th>
                                                    <th>" . localize('Viite') . "</th>	
                                            </tr>";

        foreach ($response as $creditInfo) {
            echo "<tr>	
                                                    <td>" . htmlspecialchars($creditInfo['date']) . "</td>
                                                    <td>" . htmlspecialchars($creditInfo['summa']) . "&euro;</td>
                                                    <td>" . htmlspecialchars($creditInfo['maksaja']) . "</td>
                                                    <td><a name='ref' href='index.php?sivu=creditInfos&amp;ref=" . htmlspecialchars($creditInfo['arkistotunnus']) . "'>" . htmlspecialchars($creditInfo['viite']) . "</a></td>
                                                    ";
        }
        echo "
                                            </tr>
                                    </table>
                            </div>";
    }
}
?>
