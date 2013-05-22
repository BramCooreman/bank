<?php
/**
 * @file
 * Kirjautuminen
 *
 * TAMKin oppimisympÃ¤ristÃ¶, Ainopankki
 * kirjautuminen.php
 * Annika Granlund, Jarmo KortetjÃ¤rvi
 * Created: 28.05.2010
 * Modified: 23.08.2010
 *
 */
	require_once 'functions.php';
	
	// Jos autentikointi onnistuu
	if (isset($_SESSION[ 'Authenticated' ]) && ($_SESSION[ 'Authenticated' ] == 1)) {
		// Tulostaa sivun sisällön
		print "<h1>".localize('Tervetuloa Ainopankkiin!')."</h1>
				<div class='content padding20'>	
					<p class='text'>".localize('Ainopankki tarjoaa asiakkailleen kattavia ja monipuolisia pankkipalveluja. Sillä on toimintaa ympäri Suomea, yhteensä 10 konttoria suurimmissa kaupungeissa. Vuoden 2010 alussa Ainopankin palveluksessa oli yhteensä 300 henkilöä.')."</p>
					<p class='text'>".localize('Pankki on panostanut erityisesti verkkopalveluihin, jolloin konttoreiden toimintaa on pystytty tehostamaan. Ainopankki on erityisesti keskittynyt huolehtimaan yritysten pankkitoiminnasta, mutta asiakkaista löytyy myös yritysasiakkaiden työntekijöitä sekä omistajia. Suomessa asiakkaita on yhteensä 18 000, joista 7 500 on yritysasiakkaita. Kaikki asiakkaat ovat myös verkkopankkiasiakkaita, joka tekee Ainopankista merkittävän toimijan verkkoasioinnissa. Yrityksen tulos ennen veroja oli 28 miljoonaa euroa vuonna 2009.')."</p> 
					<p class='text'>".localize('Ainopankin arvoihin ja strategiaan kuuluu asiakkaiden ja toimintaympäristön taloudellisen menestyksen turvaaminen. Päätavoitteena on tarjota asiakkaiden tarvitsemia palveluja mahdollisimman kilpailukykyisesti.')."</p>
                                    <p>".localize('Ongelmatilanteissa ota yhteyttä')." <a href='mailto:ainopankki@kykylaakso.fi'>ainopankki@kykylaakso.fi</a></p> 
				</div>
				";
	} 
	// Jos käyttäjänimi tai salasana meni väärin
	else if (isset($_SESSION[ 'Authenticated' ]) && ($_SESSION[ 'Authenticated' ] == 0)) {
		print '<p class="notValid">'.localize('Kirjoitit käyttäjätunnuksen tai salasanan väärin.').' </p>';
		
		// Tuhotaan istunto
		session_destroy();
		session_start();
		$_SYSTEM['lang'] = 'fin';
		printAuthenticationForm();
	}
	// Käyttäjä kirjautuu ensimmäistä kertaa
	else {
		if(!isset($_SESSION)) 
			session_start();
		
		$lang = (string) $_COOKIE['lang'];
		$_SYSTEM['lang'] = $lang;
		printAuthenticationForm();
	}
	
	/**
	 * Tulostetaan kirjautumislomake
	 */
	function printAuthenticationForm() {
		print '	<div id="login">
				<p id="tervetuloa">'.localize('Tervetuloa Ainopankkiin!').'</p>
					<div id="kirjaudulomake">
						<p id="kirjaudu">'.localize('Kirjaudu sisään:').' </p>
						<form action="index.php" method="post">
							<table id="authentication">
							<tr>
							<td>'.localize('Käyttäjätunnus:').'</td>
							<td>
							<input type="text" name="username" size="20" maxlength="30" class="kentta" />
							</td>
							</tr>
							<tr>
							<td>'.localize('Salasana:').'</td>
							<td><input type="password" name="password" size="20" maxlength="15" class="kentta" />
							</td>
							</tr>
							</table>
							<p id="painikkeet">
							<input type="submit" name="login" value='.localize('KIRJAUDU').' class="painike"/>
							<input type="reset" value='.localize('TYHJENNÄ').' class="painike"/>
							</p>
						
						</form>
					</div><!-- /kirjaudu -->
				</div><!-- /login -->';
	}
?>
