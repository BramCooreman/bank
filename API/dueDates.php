<?php
require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

$today = date('Y-m-d');
$yhtio = $_SESSION['yhtionNimi'];
$ytunnus = $_SESSION['ytunnus'];

print '	<h1>' . localize('Erääntyvät maksut') . '</h1>
                    <p class="yhtionTiedot"><strong>' . $yhtio . '</strong></p>';

//HTTPS url to the code behind where the data is processed.
$url = 'https://localhost/bank/API/dueDate.php/' . $ytunnus . "?lang=" . $_SESSION['lang'];

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
$response = curl_exec($client);
curl_close($client);
$responseUTF8 = utf8_encode($response);
$xml = simplexml_load_string($responseUTF8);

foreach ($xml->dueDate as $dueDate) {
    if (htmlspecialchars($dueDate->rowsTotal) == 0) {
        echo "<p>" . localize('Pankkitiliä ei löydy') . "</p>";
    } else {
        echo "<table class='tilinTiedot'><tr><td>" . localize('Tilinro:') . "</td><td>" . htmlspecialchars($dueDate->tilinro) . "</td></tr>";
        $printToday = date('d.m.Y', strtotime($today));

        echo "<tr><td>" . localize('Erääntyvät maksut') . " </td><td>"
        . $printToday . "</td></tr></table>";
        foreach ($dueDate->data as $data) {
            if (htmlspecialchars($data->rows) == 0) {
                echo "<div class='content padding20'>
                            <p>" . localize('Ei erääntyviä maksuja') . "</p>

                            <table id='tilioteTable'>";
            } else {
                echo "	<div class='content'>
                            <table id='tilioteTable'>
                                    <tr>
                                            <th>Tap.pvm</th>
                                            <th>Saajan nimi</th>
                                            <th>Maksajan nimi</th>
                                            <th>Summa</th>
                                            <th>Selite</th>
                                            <th>Poista</th>
                                    </tr>
                            ";
                $i = 1;
                foreach ($data->row as $row) {
                    echo "<tr";
                    if ($i % 2 == 1)
                        echo " class='oddRow'";
                    $i++;
                    echo "><td>" . htmlspecialchars($row->tapvm) . "</td>
                                    <td>" . htmlspecialchars($row->saajanNimi) . "</td>
                                    <td>" . htmlspecialchars($row->maksajanNimi) . "</td>
                                    <td>" . htmlspecialchars($row->summa) . "</td>
                                     <td>" . htmlspecialchars($row->selite) . "</td>       
                            <td>
                                                    <form action='' method='post'>";
                    ?><input type='submit' name='poista' value='x' onclick="javascript: return confirm('Oletko varma, että haluat poistaa tapahtuman?');"/><?php
                    echo "			<input type='hidden' name='tapahtuma' value='" . htmlspecialchars($row->arkistotunnus) . "'/>
                                                    </form>
                                            </td>
                                    </tr>
                                    ";
                }
            }
            echo "
                    </table>";

            echo "</div>"; //div tilitapahtumat
        }
    }
}

if (isset($_POST['poista']) && isset($_POST['tapahtuma'])) {

    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/dueDate.php/' . $ytunnus;

    //Pass the data that the user has entered
    $data = "<dueDates><dueDate><tapahtuma>" . $_POST['tapahtuma'] . "</tapahtuma></dueDate></dueDates>";

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
    $response = curl_exec($ch);

    curl_close($ch);
}
?>
