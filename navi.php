<?php
/**
 * @file
 * Navigaatio
 *
 * TAMKin oppimisympäristö, Ainopankki
 * navi.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 03.08.2010
 * Modified: 2010-01-27
 *
 */
	// Ainopankki, navigaatio
	
	require_once './lib/functions.php';
	
	print '<div id="left">
                    <ul id="navi">';		
                            if (isset($_SESSION[ 'Authenticated' ]) && ($_SESSION[ 'Authenticated' ] == 1)) {
                                    echo '
                                            <li><a href="index.php" class="etusivu" >'.localize('Etusivu').'</a></li>
                                            <li><a href="index.php?sivu=payments" class="payments">'.localize('Uusi maksu').'</a></li>
                                            <li><a href="index.php?sivu=dueDates" class="dueDates" >'.localize('Erääntyvät maksut').'</a></li>
                                            <li><a href="index.php?sivu=transactions" class="transactions" >'.localize('Tilitapahtumat').'</a></li>
                                            <li><a href="index.php?sivu=creditInfos" class="creditInfos">'.localize('Luoton tiedot').'</a></li>';

                                            if($_SESSION['kayttaja'] == 'superuser')
                                            {
                                                echo '
                                            <li><a href="index.php?sivu=transfers" class="transfers">'.localize('Siirrä rahaa').'</a></li>';
                                            }
                                    print '<li><a href="index.php?sivu=roles" class="roles">'.localize('Asiakasroolin vaihto').'</a></li>';
                                    print '<li><a href=index.php?sivu=login&amp;logout=1>'.localize('Kirjaudu ulos').'</a></li>';
                            }
	
	print        '</ul><!-- /navi -->
				<img id="naviLogo" src="images/navilogo.png" alt="Ainopankki - ainoa pankkisi" />
			</div> <!-- /left -->
			';

?>
