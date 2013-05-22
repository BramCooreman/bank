<?php
/**
 * @file
 * Roolin vaihtaminen
 *
 * TAMKin oppimisympäristö, Ainopankki
 * roolinVaihto.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 19.04.2010
 * Modified: 23.08.2010
 *
 */
 
echo "<h1>".localize('Asiakasroolin vaihto')."</h1>";
echo '<div class="content padding20">';

databaseConnect();

// Pyydetään roolin vaihtoa
if(isset($_POST['vaihdaRooli'])){
	$role = $_POST['asiakasrooli']; /**< INT, Y-tunnus, Käyttäjän valitsema rooli */
	$user = $_SESSION[ 'kayttaja' ]; /**< STRING, Nimi, Käyttäjän sen hetkinen rooli */
	
	$query = "	SELECT		kuka.kuka 		AS 'kuka' 
					, kuka.yhtio 			AS 'yhtio' 
					, yhtio.nimi 			AS 'yhtionNimi' 
					, TAMK_pankkitili.tilinro 	AS 'tilinro'
					, yhtio.ytunnus			AS 'ytunnus'
				FROM		kuka 
				JOIN		yhtio
				ON			kuka.yhtio = yhtio.yhtio 
				JOIN		TAMK_pankkitili
				ON			yhtio.ytunnus = TAMK_pankkitili.ytunnus
				WHERE		kuka.kuka = '$user' 
						AND yhtio.ytunnus = '$role'
				; ";

	$resultSet = mysql_query($query);
	$row = mysql_fetch_assoc($resultSet);
	

	// Tarkastetaan saadaanko tuloksia kyselylle ja vaihdetaan roolin tiedot käyttäjän tiedoiksi
	//Check the results of the query is received and the exchange of information on the role of user data
	$num_rows = mysql_num_rows($resultSet);
	if ( $num_rows != 0 ) {
		$_SESSION[ 'ytunnus' ] = $row[ 'ytunnus' ];
		$_SESSION[ 'yhtionNimi' ] = $row[ 'yhtionNimi' ];
		$_SESSION[ 'tilinro' ] = $row[ 'tilinro' ];
		header("Location: index.php?sivu=roolinVaihto");
		echo "<p>Asiakasrooli vaihdettu!</p>";
	}
	else {
		header("Location: index.php?sivu=roolinVaihto");
		echo "<p>".localize('Virhe vaihdettaessa asiakasroolia!')."</p>";
	}
}

// Haetaan tietokannasta roolin vaihtoon mahdolliset yritykset
// ja listataan valikkoon käyttäjän valitsettavaksi
// Retrieved from the database role of the exchange potential companies listed on the menu, and select the user mode
echo "<p>".localize('Valitse asiakasrooli:')."</p>";

$user = $_SESSION[ 'kayttaja' ];
$query =  "	
		SELECT  kuka.yhtio		AS 'yhtio'
			, yhtio.nimi 		AS 'yhtionNimi'
			, yhtio.ytunnus		AS 'ytunnus'
		FROM kuka
		JOIN		yhtio
		ON		kuka.yhtio = yhtio.yhtio
		WHERE kuka = '$user'
		ORDER BY yhtionNimi ASC
		";

$result = mysql_query($query);

echo "<form action='' method='post'><select name='asiakasrooli'>";

// Tulostus listaan
while($row = mysql_fetch_array($result)) {
	echo "<option value=".$row['ytunnus'];
	if($row['ytunnus'] == $_SESSION['ytunnus']){
		echo " selected='selected'";
	}
	echo ">".$row['yhtionNimi']."</option>\n";
}

echo "</select><input class='painike' type='submit' name='vaihdaRooli' value=".localize('VAIHDA')." /></form>";
echo "<p class='paddingTop'>".localize('Roolisi nyt:')." <strong>".$_SESSION[ 'yhtionNimi' ]."</strong> </p>";
echo "</div>";
?>
