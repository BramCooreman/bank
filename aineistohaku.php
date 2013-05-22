<?php
/**
 * @file
 * Ainopankin aineistohaku
 * 
 * TAMKin oppimisympäristö, Ainopankki
 * aineistohaku.php
 * Author: Annika Granlund, Jarmo Kortetjärvi
 * Created: 21.04.2010
 * Modified: 23.08.2010
 *
 */

if( $_POST[ 'haeViiteaineisto' ]) 
	require_once 'saapuvatViitemaksut.php';


if( $_POST[ 'haeTiliote' ]) {
	require_once 'konekielinenTiliote.php';
	
	/**
	 * Y-tunnuksen tarkistus
	 *
	 * @see konekielinenTiliote.php
	 */
	teeKonekielinenTiliote($_SESSION[ 'ytunnus' ]); 
}

echo '<h1>Aineistohaku</h1>';
echo '<div class="content padding20">';


// Viiteaineiston hakulomake
//Reference material form
echo '<form action="" method="post">
				<table class="aineistohaku">
					<tr>
						<td>				
							<p>Hae viiteaineisto</p>
						</td>
						<td>
							<input type="submit" name="haeViiteaineisto" value="HAE" class="painike"/>
							<!--<input type="reset" value="TYHJENNÄ" class="painike"/>-->
						</td>
					</tr>
				</table>
				
			</p>
		</form>';
		

// Konekielisen tiliotteen hakulomake
// Electronic account statement form
echo '<form action="" method="post">
			<table class="aineistohaku">
				<tr>
					<td>
						<p>Hae konekielinen tiliote</p>
					</td>
					<td>
						<input type="submit" name="haeTiliote" value="HAE" class="painike"/>
						<!--<input type="reset" value="TYHJENNÄ" class="painike"/>-->
					</td>
				</tr>
			</table>
		</form>';
echo '</div>';

?>
