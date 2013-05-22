<?php
/**
 * @file
 * Sisäänkirjautuminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * login.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 23.08.2010
 *
 */
echo "<div id='loginbar'>";

require_once 'functions.php';

if (isset($_POST[ 'login' ])) {

	if ( (isset($_POST[ 'username' ])) && (!empty($_POST[ 'username' ])) 
	&& (isset($_POST[ 'password' ])) && (!empty($_POST[ 'password' ])) ) {
            
                $url = 'https://localhost/bank/token.php';

                $data = "grant_type=client_credentials";
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);

                curl_close($ch);

                $responseUTF8 = utf8_encode($response);
                $xml = simplexml_load_string($responseUTF8);
		databaseConnect();
		
		// Tallennetaan käyttäjänimi ja salasana muuttujiin
		$userName = mysql_real_escape_string($_POST[ 'username' ]);
		$password = mysql_real_escape_string($_POST[ 'password' ]);
		
		$query = "	SELECT		kuka.kuka 			AS 'kuka' 
								, kuka.salasana 	AS 'salasana' 
								, yhtio.ytunnus 	AS 'ytunnus' 
								, yhtio.nimi 		AS 'yhtionNimi' 
								, TAMK_pankkitili.tilinro AS 'tilinro' 
								, kuka.profiilit	AS 'profiili'
					FROM		kuka 
					JOIN		yhtio
					ON			kuka.yhtio = yhtio.yhtio 
					JOIN		TAMK_pankkitili
					ON			yhtio.ytunnus = TAMK_pankkitili.ytunnus
					WHERE		kuka.kuka = '$userName' 
					; ";
					
		$resultSet = mysql_query($query);
		
		// Tarkastetaan löytyykö tietoja tietokannasta
		$num_rows = mysql_num_rows($resultSet);
		
		// Ei tietoa, ei oikea käyttäjänimi
		if ( $num_rows == 0 ) {
			$_SESSION[ 'Authenticated' ] = 0;
		} 
		// Tietoa löytyy, oikea käyttäjänimi
		else {
			$row = mysql_fetch_assoc($resultSet);
			$passwordFromDB = utf8_encode( $row[ 'salasana' ] );
			
			if ($passwordFromDB == md5($password)) {
				$_SESSION[ 'Authenticated' ] = 1;
				$_SESSION[ 'ytunnus' ] = $row[ 'ytunnus' ];
				$_SESSION[ 'yhtionNimi' ] = $row[ 'yhtionNimi' ];
				$_SESSION[ 'kayttaja' ] = $row[ 'kuka' ];
				$_SESSION[ 'tilinro' ] = $row[ 'tilinro' ];
				
				if($row[ 'profiili' ] == 'Admin profil'){
					$_SESSION[ 'rooli' ] = 1;
				}
				
			} else {
				$_SESSION[ 'Authenticated' ] = 0;
			}
		}
	}
	session_write_close();
	header("Location: index.php");
}


// Uloskirjautuminen
if (isset($_GET[ 'logout' ])) {
	session_destroy();
	header("Location: index.php");
}


// Käyttäjä on kirjautunut sisään
	if(isset($_SESSION[ 'Authenticated' ])){
		echo "<p>".localize('Kirjautuneena sisään')." <br/><strong>" . $_SESSION['yhtionNimi'] . " / " . $_SESSION[ 'kayttaja' ] . "</strong></p>";
		echo "<p><a href=index.php?sivu=login&amp;logout=1>".localize('Kirjaudu ulos')."</a></p>";
		}
// Käyttäjä ei ole kirjautunut sisään
	else{
		echo "<p>".localize('Et ole kirjautuneena sisään')."</p>";
		echo "<p><a href=index.php>".localize('Kirjaudu sisään')."</a></p>";
	}
	
?>
</div> <!-- /loginbar -->
