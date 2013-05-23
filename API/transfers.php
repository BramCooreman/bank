<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';
//Variables
$errorText = "";
$saajanNimi = "";
$summa = "";
$saaja = "";

echo "<h1>" . localize('Siirrä rahaa') . "</h1>";
echo '<div class="content padding20">';

//Did the user pressed transfers?
if (isset($_POST['transfers'])) {
    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/transfer.php?laatija=' . $_SESSION['kayttaja'];

    //Pass the data that the user has entered
    $data = json_encode(array("maksupvm" => $_POST['maksunErapaiva'], "saajanNimi" => $_POST['saajanNimi'],
        "saajanTili" => $_POST['saajanTili'], "viite" => $_POST['viesti'],
        "summa" => $_POST['summa']));

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
    //Decode the JSON object as an array
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (htmlspecialchars($response['result']) === "correct") {
        print "
                    <p class='paddingBottom'>" . localize('Maksu suoritettu.') . "</p>
                    <p class='link'><a href='index.php?sivu=payments'>" . localize('Tee uusi maksu') . "</a></p>
            ";
    } else {
        header("Location: index.php?sivu=errorPage");
    }
} else {
    //Did the user pressed jatka?
    if (isset($_POST['jatka'])) {
        $saaja = mysql_real_escape_string($_POST['saajanTili']);
        $saajanNimi = mysql_real_escape_string($_POST['saajanNimi']);
        $summa = mysql_real_escape_string(str_replace(',', '.', $_POST['summa']));
        $maksunErapaiva = date("d.m.Y");
        $viesti = localize('Ylläpitäjän suorittama rahan siirto');

        //Check if the values that are entered are correct
        if (empty($saaja)) {
            $errorText = localize('Tilinumero on virheellinen tai tyhjä.');
        }

        if ($summa <= 0) {
            $errorText = localize('Syötä maksun summa.');
        }

        if (!$saajanNimi) {
            $errorText = localize('Anna saajan nimi.');
        }

        if (!isDataNumeric($summa)) {
            $errorText = localize('Tarkista summa.');
        }
    }

    if (isset($_POST['jatka']) && !$errorText) {
        echo "	<div id='uusiMaksuLomake' class='content padding20'>
                        <p id='uusiMaksu'>" . localize('Hyväksy maksu') . "</p>";

        echo "<table id='hyvaksyTable'>";
        echo getPossibleTableRow(localize('Saajan tilinumero'), $saaja, true);
        echo getPossibleTableRow(localize('Saajan nimi'), $saajanNimi, false, true);
        echo getPossibleTableRow(localize('Eräpäivä'), $maksunErapaiva);
        echo getPossibleTableRow(localize('Maksun määrä'), number_format($summa, 2, ',', ' '), true);
        echo getPossibleTableRow(localize('Viesti'), $viesti);
        echo "</table>";

        echo "<form action='' method='post'>";
        echo getPossibleHiddenField($maksunErapaiva, "maksunErapaiva");
        echo getPossibleHiddenField($saajanNimi, "saajanNimi");
        echo getPossibleHiddenField($saaja, "saajanTili");
        echo getPossibleHiddenField($summa, "summa");
        echo getPossibleHiddenField($viesti, "viesti");

        echo "<p id='painikkeet'>
                                        <input type='submit' name='muuta' value='<< " . localize('MUUTA TIETOJA') . "' class='painike'> 
                                        <input type='submit' name='transfers' value=" . localize('HYVÄKSY') . " class='painike'> 
                        </p>";
        echo "</form></div><!-- uusimaksulomake -->";
    } else {
        print ' <div id="uusiMaksuLomake" class="content padding20">
                    <p> * ' . localize('pakollinen kenttä') . '</p>
                    <p class="errorMessage">' . $errorText . '</p>
                            <form action="" method="post" >
                                    <table id="uusiMaksuKentat">
                                            <tr>
                                            <td>' . localize('Saajan tilinumero') . ' *</td>
                                            <td>
                                            <input type="text" name="saajanTili" value="' . $saaja . '" size="20" maxlength="22" class="kentta" onkeypress="return disableEnterKey(event)"/>
                                            </td>
                                            </tr>
                                            <tr>
                                            <td>' . localize('Saajan nimi') . ' *</td>
                                            <td><input type="text" name="saajanNimi" value="' . $saajanNimi . '" size="20" maxlength="35" class="kentta" onkeypress="return disableEnterKey(event)"/>
                                            </td>
                                            </tr>
                                            <tr>
                                            <td>' . localize('Maksun määrä') . ' *</td>
                                            <td><input type="text" name="summa" value="' . $summa . '" size="10" maxlength="19" class="kentta" id="maksunMaaraKentta" onkeypress="return disableEnterKey(event)"/>EUR</td>
                                            </tr>
                                            <tr>
                                            <td>' . localize('Tilioteteksti') . '</td>
                                            <td>' . localize('tilisiirto') . '</td>
                                            </tr>
                                            <tr>
                                            <td>' . localize('Viesti') . '</td>
                                            <td>' . localize("Ylläpitäjän suorittama rahan siirto") . '</td>
                                            </tr>
                                    </table>

                                    <p id="painikkeet">
                                    <input type="submit" name="tyhjenna" value=' . localize('TYHJENNÄ') . ' class="painike"/>
                                    <input type="submit" name="jatka" value=' . localize('JATKA') . ' class="painike"/>
                                    </p>
                            </form>
         </div><!-- uusiMaksuLomake -->';
    }
}
echo '</div>';
?>
