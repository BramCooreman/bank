<?php
// error reporting (this is a demo, after all!)
ini_set('display_errors',1);error_reporting(E_ALL);

// Autoloading (composer is preferred, but for this example let's just do this)
require_once('./oauth2-server/src/OAuth2/Autoloader.php');
OAuth2_Autoloader::register();

// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
$dsn = "mysql:host=127.0.0.1;dbname=oauthdb";

$username = "root";
$password = "";
$storage = new OAuth2_Storage_Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2_Server($storage);

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2_GrantType_UserCredentials($storage));
$server->addGrantType(new OAuth2_GrantType_ClientCredentials($storage));
$server->addGrantType(new OAuth2_GrantType_AuthorizationCode($storage));

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$server->handleTokenRequest(OAuth2_Request::createFromGlobals(), new OAuth2_Response())->send();

?>