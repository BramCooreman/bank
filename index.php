<?php
/**
 * @file
 * Pääsivu
 *
 * TAMKin oppimisympäristö, Ainopankki
 * index.php
 * Annika Granlund, Jarmo Kortetjärvi
 * Created: 28.05.2010
 * Modified: 23.08.2010
 *
 */

ini_set("session.gc_maxlifetime", "1800"); 
session_set_cookie_params(1800);
session_name("ainopankki");
session_start();

include_once './lib/functions.php';

validateSession();

if(isset($_GET['lang']))
{
	$lang = (string) $_GET['lang'];
	setcookie('lang', $lang, time() + 60*60*24*30);
        defineLang($lang);
	header("Location: index.php");
}

if(!isset($_COOKIE['lang'])) 
{
	$lang = 'fin';
	setcookie('lang', $lang, time() + 60*60*24*30);
        defineLang($lang);
	header("Location: index.php");
}

else {
	$lang = (string) $_COOKIE['lang'];
        defineLang($lang);
}

$_SESSION['lang'] = $lang;
//defineLang($lang);
// Transferred to the login page if the user is not authenticated (session has expired, etc.)
if(isset($_GET[ 'sivu' ]) && $_SESSION[ 'Authenticated' ] != 1){
	header("Location: index.php");
	session_start();
	$language = 'fin';
	$_SESSION['lang'] = $language;
        defineLang($language);
	exit;
}

// Log Off
if (isset($_GET[ 'logout' ])) {
	header("Location: index.php");
	$language = $_SESSION['lang'];
	session_destroy();
	session_start();
	$_SESSION['lang'] = $language;
        defineLang($language);
	exit;
}

// Saving a new payment database
if (isset($_POST['hyvaksyMaksu'])){
	require_once 'hyvaksyMaksu.php';
}

//agree to the transfer
if (isset($_POST['hyvaksySiirto'])) {
	require_once 'hyvaksySiirto.php';
}

// Searching reference material and will be saved in a text file
if ( isset($_POST[ 'haeViiteaineisto' ] )) {
	require( 'saapuvatViitemaksut.php' );
	exit;
}

// Searching electronic account statement and stored in a text file
if ( isset($_POST[ 'haeTiliote' ] )) {
	require( 'konekielinenTiliote.php' );
	exit;
}

// Swap the role of
if ( isset($_POST[ 'vaihdaRooli' ] )) {
	require( 'roolinVaihto.php' );
}
defineLang($_SESSION['lang']);
header("Content-Type: text/html; charset=ISO-8859-1"); 
	echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fi" lang="fi">
	<head>
		<title>Ainopankki</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/> 
		<link rel="stylesheet" type="text/css" href="css/ainopankki_style.css"/>
		<link rel="shortcut icon" href="images/aino_favicon.png" />
		
		<script src="../lib/calendar/calendar.js" type="text/javascript"></script>
		<link href="../lib/calendar/calendar.css" type="text/css" rel="stylesheet" />
		<script src="../lib/js/disable_enter.js" type="text/javascript"></script>
		<script src="../lib/js/confirm.js" type="text/javascript"></script>
	</head>
	
	<?php
        

	if(isset($_GET['sivu'])){
		$sivu = $_GET[ 'sivu' ];
		}
		if ( empty($sivu) ) {
			$sivu = 'etusivu';
		}
		print '<body class="' . $sivu . '">';
	?>
		<div id="pohja">
	
			<?php
				require_once 'header.php';
				require_once './API/loginbar.php';
				require_once 'navi.php';
				require_once 'content.php';
				require_once 'footer.php';
			?>
		</div><!-- /pohja -->
	
	</body>
</html>
