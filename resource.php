<?php
// error reporting again
ini_set('display_errors',1);error_reporting(E_ALL);

// Autoloading again
require_once('./oauth2-server/src/OAuth2/Autoloader.php');
OAuth2_Autoloader::register();

// create your storage again
$dsn = "mysql:host=127.0.0.1;dbname=oauthdb";

$username = "root";
$password = "";
$storage = new OAuth2_Storage_Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));

// create your server again
$server = new OAuth2_Server($storage);

// Handle a request for an OAuth2.0 Access Token and send the response to the client
if (!$server->verifyResourceRequest(OAuth2_Request::createFromGlobals(), new OAuth2_Response())) {
    $server->getResponse()->send();
    die;
}
echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
?>