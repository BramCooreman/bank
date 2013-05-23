<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

//Does the user want to login?
if (isset($_POST['login'])) {

    if ((isset($_POST['username'])) && (!empty($_POST['username'])) && (isset($_POST['password'])) && (!empty($_POST['password']))) {

        $userName = mysql_real_escape_string($_POST['username']);
        $password = mysql_real_escape_string($_POST['password']);

        //HTTPS url to the code behind where the data is processed.
        $url = 'https://localhost/bank/API/user.php';

        //Pass the data that the user has entered
        $data = json_encode(array("username" => $userName, "password" => $password));

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
        //Get the values that are send back to the user as an array
        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);


        $_SESSION['Authenticated'] = htmlspecialchars($response['authenticated']);
        $_SESSION['ytunnus'] = htmlspecialchars($response['ytunnus']);
        $_SESSION['yhtionNimi'] = htmlspecialchars($response['yhtionNimi']);
        $_SESSION['kayttaja'] = htmlspecialchars($response['kayttaja']);
        $_SESSION['tilinro'] = htmlspecialchars($response['tilinro']);

        if (htmlspecialchars($response['profiili']) == 'Admin profil') {
            $_SESSION['rooli'] = 1;
        }
    }
    session_write_close();
    header("Location: index.php");
}

if (isset($_SESSION['Authenticated']) && ($_SESSION['Authenticated'] == 1)) {
    // Tulostaa sivun sisällön
    print "<h1>" . localize('Tervetuloa Ainopankkiin!') . "</h1>
                        <div class='content padding20'>	
                                <p class='text'>" . localize('Ainopankki tarjoaa asiakkailleen kattavia ja monipuolisia pankkipalveluja. Sillä on toimintaa ympäri Suomea, yhteensä 10 konttoria suurimmissa kaupungeissa. Vuoden 2010 alussa Ainopankin palveluksessa oli yhteensä 300 henkilöä.') . "</p>
                                <p class='text'>" . localize('Pankki on panostanut erityisesti verkkopalveluihin, jolloin konttoreiden toimintaa on pystytty tehostamaan. Ainopankki on erityisesti keskittynyt huolehtimaan yritysten pankkitoiminnasta, mutta asiakkaista löytyy myös yritysasiakkaiden työntekijöitä sekä omistajia. Suomessa asiakkaita on yhteensä 18 000, joista 7 500 on yritysasiakkaita. Kaikki asiakkaat ovat myös verkkopankkiasiakkaita, joka tekee Ainopankista merkittävän toimijan verkkoasioinnissa. Yrityksen tulos ennen veroja oli 28 miljoonaa euroa vuonna 2009.') . "</p> 
                                <p class='text'>" . localize('Ainopankin arvoihin ja strategiaan kuuluu asiakkaiden ja toimintaympäristön taloudellisen menestyksen turvaaminen. Päätavoitteena on tarjota asiakkaiden tarvitsemia palveluja mahdollisimman kilpailukykyisesti.') . "</p>
                            <p>" . localize('Ongelmatilanteissa ota yhteyttä') . " <a href='mailto:ainopankki@kykylaakso.fi'>ainopankki@kykylaakso.fi</a></p> 
                        </div>
                        ";
}
// Jos käyttäjänimi tai salasana meni väärin
else if (isset($_SESSION['Authenticated']) && ($_SESSION['Authenticated'] == 0)) {
    print '<p class="notValid">' . localize('Kirjoitit käyttäjätunnuksen tai salasanan väärin.') . ' </p>';

    // Tuhotaan istunto
    session_destroy();
    session_start();
    $_SYSTEM['lang'] = 'fin';
    print_authenticationForm();
}
// Käyttäjä kirjautuu ensimmäistä kertaa
else {
    if (!isset($_SESSION))
        session_start();

    $lang = (string) $_COOKIE['lang'];
    $_SYSTEM['lang'] = $lang;
    print_authenticationForm();
}

/**
 * Prints the authentication form
 * @return string XML format 
 */
function print_authenticationForm() {
    echo '<div id="login">
            <p id="tervetuloa">' . localize('Tervetuloa Ainopankkiin!') . '</p>
                    <div id="kirjaudulomake">
                            <p id="kirjaudu">' . localize('Kirjaudu sisään:') . ' </p>
                            <form action="" method="post">
                                    <table id="authentication">
                                    <tr>
                                    <td>' . localize('Käyttäjätunnus:') . '</td>
                                    <td>
                                    <input type="text" name="username" size="20" maxlength="30" class="kentta" />
                                    </td>
                                    </tr>
                                    <tr>
                                    <td>' . localize('Salasana:') . '</td>
                                    <td><input type="password" name="password" size="20" maxlength="15" class="kentta" />
                                    </td>
                                    </tr>
                                    </table>
                                    <p id="painikkeet">
                                    <input type="submit" name="login" value=' . localize('KIRJAUDU') . ' class="painike"/>
                                    <input type="reset" value=' . localize('TYHJENNÄ') . ' class="painike"/>
                                    </p>

                            </form>
                    </div><!-- /kirjaudu -->
            </div><!-- /login -->';
}

?>
