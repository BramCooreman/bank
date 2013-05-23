<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

//variables
$maksunErapaiva = '';
$errorText = '';
$saaja = '';
$saajanNimi = '';
$summa = '';
$viite = '';
$viesti = '';

echo "<h1>" . localize('Uusi maksu') . "</h1>";
//Check if the user has pressed payments or not
if (isset($_POST['payments'])) {
    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/payment.php?ytunnus=' . $_SESSION['ytunnus'] . '&laatija=' . $_SESSION['kayttaja'] . "&lang=" . $_SESSION['lang'];

    //Pass the data that the user has entered
    $data = json_encode(array("maksupvm" => $_POST['maksupvm'], "saajanNimi" => $_POST['saajanNimi'],
        "saajanTili" => $_POST['saajanTili'], "viite" => $_POST['viite'],
        "viesti" => $_POST['viesti'],
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
    // Get the values that are send back to the user as an array
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
    if ((isset($_POST['jatka']) || isset($_POST['muuta'])) && !isset($_POST['tyhjenna'])) {
        $maksunErapaiva = mysql_real_escape_string($_POST['maksunErapaiva']);
        $maksupvm = mysql_real_escape_string(substr($maksunErapaiva, 6, 4) . '-' . substr($maksunErapaiva, 3, 2) . '-' . substr($maksunErapaiva, 0, 2));
        $maksajanNimi = mysql_real_escape_string($_POST['maksajanNimi']);
        $saajanNimi = mysql_real_escape_string($_POST['saajanNimi']);
        $maksaja = mysql_real_escape_string($_POST['maksajanTili']);
        $saaja = mysql_real_escape_string($_POST['saajanTili']);
        $viite = mysql_real_escape_string($_POST['viite']);
        $viesti = mysql_real_escape_string($_POST['viesti']);
        $summa = mysql_real_escape_string(str_replace(',', '.', $_POST['summa']));
        $errorText = '';

        if (empty($saaja)) {
            $errorText = localize('Tilinumero on virheellinen tai tyhj√§.');
        }

        // maksulla on oltava viite tai viesti
        if (!$viite && !$viesti) {
            $errorText = localize('Maksulla on oltava viite tai viesti.');
        }

        // summan pit‰‰ olla yli 0
        if ($summa <= 0) {
            $errorText = localize('Sy√∂t√§ maksun summa.');
        }

        // saajan nimi on tyhj‰
        if (!$saajanNimi) {
            $errorText = localize('Anna saajan nimi.');
        }

        // maksun m‰‰r‰ss‰ voi olla vain numeroita
        if (!isDataNumeric($summa)) {
            $errorText = localize('Tarkista summa.');
        }

        // p‰iv‰m‰‰r‰n tarkistus
        if (!checkDateFormat("$maksunErapaiva")) {
            $errorText = localize('Tarkista p√§iv√§m√§√§r√§.');
        }

        // tarkistetaan ettei er‰p‰iv‰ ole jo mennyt
        if (strtotime($maksunErapaiva) < strtotime(date('d.m.Y'))) {
            $errorText = localize('Valitsemasi er√§p√§iv√§ on jo mennyt');
        }

        // saaja ja maksaja ei voi olla sama yritys
        if ($saaja == $maksaja) {
            $errorText = localize('Et voi maksaa omalle tilillesi.');
        }
    }

    if (isset($_POST['jatka']) && !$errorText) {
        echo "	<div id='uusiMaksuLomake' class='content padding20'>
				<p id='uusiMaksu'>" . localize('Hyv√§ksy maksu') . "</p>";

        echo "<table id='hyvaksyTable'>";
        echo getPossibleTableRow(localize('Maksajan tili'), $maksaja);
        echo getPossibleTableRow(localize('Maksajan nimi'), $maksajanNimi, false, true);
        echo getPossibleTableRow(localize('Saajan tilinumero'), $saaja, true);
        echo getPossibleTableRow(localize('Saajan nimi'), $saajanNimi, false, true);
        echo getPossibleTableRow(localize('Er√§p√§iv√§'), $maksunErapaiva);
        echo getPossibleTableRow(localize('Maksun m√§√§r√§'), number_format($summa, 2, ',', ' '), true);
        echo getPossibleTableRow(localize('Viite'), $viite);
        echo getPossibleTableRow(localize('Viesti'), $viesti);
        echo "</table>";

        echo "<form action='' method='post'>";
        echo getPossibleHiddenField($maksupvm, "maksupvm");
        echo getPossibleHiddenField($maksunErapaiva, "maksunErapaiva");
        echo getPossibleHiddenField($maksajanNimi, "maksajanNimi");
        echo getPossibleHiddenField($saajanNimi, "saajanNimi");
        echo getPossibleHiddenField($maksaja, "maksajanTili");
        echo getPossibleHiddenField($saaja, "saajanTili");
        echo getPossibleHiddenField($viite, "viite");
        echo getPossibleHiddenField($viesti, "viesti");
        echo getPossibleHiddenField($summa, "summa");

        echo "<p id='painikkeet'>
						<input type='submit' name='muuta' value='<< " . localize('MUUTA TIETOJA') . "' class='painike'> 
						<input type='submit' name='payments' value=" . localize('HYV√ÑKSY') . " class='painike'> 
				</p>";
        echo "</form></div><!-- uusimaksulomake -->";
    } else {

        // jos pvm ei ole annettu, ehdotetaan t‰t‰ p‰iv‰‰
        if (!$maksunErapaiva)
            $maksunErapaiva = date('d.m.Y');
        // uuden maksun syˆttˆ -lomake
        print ' <div id="uusiMaksuLomake" class="content padding20">
                        <p> * ' . localize('pakollinen kentt√§') . '<br/>** ' . localize('toinen kentt√§ pakollinen') . '<br/><br/></p>

                        <p class="errorMessage">' . $errorText . '</p>
                                <form action="" method="post" >
                                        <table id="uusiMaksuKentat">
                                                <tr>
                                                <td>' . localize('Maksetaan tililt√§') . '</td>
                                                <td>' . $_SESSION['tilinro'] . '
                                                <input type="hidden" name="maksajanTili" value="' . $_SESSION['tilinro'] . '" onkeypress="return disableEnterKey(event)"/></td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('Maksajan nimi') . '</td>
                                                <td>' . $_SESSION['yhtionNimi'] . '
                                                <input type="hidden" name="maksajanNimi" value="' . $_SESSION['yhtionNimi'] . '" onkeypress="return disableEnterKey(event)"/></td>
                                                </tr>
                                                <tr>
                                                <td>&nbsp;</td>
                                                </tr>

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
                                                <td>' . localize('Er√§p√§iv√§') . ' *</td>
                                                <td>
                                                <input type="text" name="maksunErapaiva" size="10" maxlength="10" class="pvmKentta" id="date" value="' . $maksunErapaiva . '" onkeypress="return disableEnterKey(event)"/>
                                                        <script type="text/javascript">
                                                                calendar.set("date");
                                                        </script>
                                                </td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('Maksun m√§√§r√§') . ' *</td>
                                                <td><input type="text" name="summa" value="' . $summa . '" size="10" maxlength="19" class="kentta" id="maksunMaaraKentta" onkeypress="return disableEnterKey(event)"/>EUR</td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('Viite') . ' **</td>
                                                <td><input type="text" name="viite" value="' . $viite . '" size="20" maxlength="20" class="kentta" onkeypress="return disableEnterKey(event)"/></td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('Viesti') . ' **</td>
                                                <td><textarea class="kentta" name="viesti" rows="3" cols="1" >' . $viesti . '</textarea></td>
                                                </tr>
                                                <tr>
                                                <td>' . localize('Tilioteteksti') . '</td>
                                                <td>' . localize('tilisiirto') . '</td>
                                                </tr>
                                        </table>

                                        <p id="painikkeet">
                                        <input type="submit" name="tyhjenna" value=' . localize("TYHJENN√Ñ") . ' class="painike"/>
                                        <input type="submit" name="jatka" value=' . localize('JATKA') . ' class="painike"/>
                                        </p>
                                </form>
                </div><!-- uusiMaksuLomake -->';
    }
}
?>
