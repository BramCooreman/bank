<?php
/**
 * @file
 * Euriborin tarkistus
 *
 * TAMKin oppimisympäristö, Ainopankki
 * tarkistaEuribor.php
 * Author: Jarmo Kortetjärvi
 * Created: 14.09.2010
 * Modified: 14.09.2010
 *
 */
 
/** 
 * Palauttaa Rss-informaation
 *
 * @param $link
 *   linkki nettisivustolle
 *
 * @return $arrFeeds
 *   Taulukko, joka sisältää titlet
 */
function getRssInformationInTable( $link ) {
	$doc = new DOMDocument();
	$doc->load( $link );
	
	$arrFeeds = array();
	foreach ($doc->getElementsByTagName('item') as $node) {
		$itemRSS = array ( 
			'title' => $node->getElementsByTagName('title')->item(0)->nodeValue
			);
		
		// Haetaan ainoastaan 365 euribor
		if(strstr($itemRSS['title'], 'act/365') && !strstr($itemRSS['title'], 'week')){
			array_push($arrFeeds, $itemRSS);
		}
	}
	
	return $arrFeeds;
}

/**
 * Palauttaa euriborin valitulle kuukaudelle
 *
 * @param $search
 *   Valittu kuukausi
 *
 * @return
 *   Euriborin arvo
 *   FALSE jos ei löydy
 */
function getRssRate($search){
	// Haetaan Rss-informaatio
	$rates = getRssInformationInTable("http://www.suomenpankki.fi/fi/_layouts/BOF/RSS.ashx/tilastot/Korot/en");
	$search =  "$search month";
	
	foreach($rates as $key => $title){
		// Erotetaan arvot muuttujiin
			// Korvataan ',' -> ':'
				$title = $title['title'];
				$title = str_replace(',',':', $title);	
			
			// Hajotetaan $title taulukoksi
				$vars = explode(":", $title);
		
		if(stristr($vars[0], $search)){
			return $vars[2];
		}
	}
	// Ei löytynyt
	return false;
}
?>