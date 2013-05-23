<?php

require_once dirname(dirname(__FILE__)) . '\lib\functions.php';

echo "<h1>" . localize('Asiakasroolin vaihto') . "</h1>";
echo '<div class="content padding20">';

//Did the user want to change role
if (isset($_POST['roles'])) {
    //HTTPS url to the code behind where the data is processed.
    $url = 'https://localhost/bank/API/role.php?user=' . $_SESSION['kayttaja'];

    //Pass the data that the user has entered
    $data = json_encode(array("asiakasrooli" => $_POST['asiakasrooli']));
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
    //Get the values that are send as an array
    $response = json_decode(curl_exec($ch), true);

    curl_close($ch);

    if (htmlspecialchars($response['result']) == "correct") {
        foreach ($response as $role) {
            $_SESSION['ytunnus'] = htmlspecialchars($role['ytunnus']);
            $_SESSION['yhtionNimi'] = htmlspecialchars($role['yhtionNimi']);
            $_SESSION['tilinro'] = htmlspecialchars($role['tilinro']);
            header("Location: index.php?sivu=roles");
            echo "<p>Asiakasrooli vaihdettu!</p>";
        }
    } else {
        header("Location: index.php?sivu=roles");
        echo "<p>" . localize('Virhe vaihdettaessa asiakasroolia!') . "</p>";
    }
}

echo "<p>" . localize('Valitse asiakasrooli:') . "</p>";
//HTTPS url to the code behind where the data is processed.
$url = 'https://localhost/bank/API/role.php?user=' . $_SESSION['kayttaja'] . "&lang=" . $_SESSION['lang'];

//Initialize the client
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
//Get the values that are send as an array
$response = json_decode(curl_exec($client), true);

curl_close($client);

echo "<form action='' method='post'><select name='asiakasrooli'>";

// Tulostus listaan
foreach ($response as $role) {
    echo "<option value=" . htmlspecialchars($role['ytunnus']);
    if (htmlspecialchars($role['ytunnus']) == $_SESSION['ytunnus']) {
        echo " selected='selected'";
    }
    echo ">" . htmlspecialchars($role['yhtionNimi']) . "</option>\n";
}

echo "</select><input class='painike' type='submit' name='roles' value=" . localize('VAIHDA') . " /></form>";
echo "<p class='paddingTop'>" . localize('Roolisi nyt:') . " <strong>" . $_SESSION['yhtionNimi'] . "</strong> </p>";
echo "</div>";
?>
