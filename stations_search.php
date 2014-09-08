<?php

require_once("utilities.php");

$searchString = $_GET["search"];

// Anropa funktionen som gör sökningen
$pointsArray = getStations($searchString);

// Sträng på JSON-format som ska hålla informationen som ska skickas till klienten.
$resultsArray ="[";

// Går igenom varje station och plockar ut information
for($i = 0 ; $i < count($pointsArray) ; $i++) {

	// Svar i JSON-format
	$resultsArray = $resultsArray . '{"name":"' . $pointsArray[$i]->Name . '","pointId":"' . $pointsArray[$i]->Id . '"},';
}

// Tar bort det sista kommatecknet och lägger till slutparentesen på arrayen.
$resultsArray = substr($resultsArray, 0, -1) . "]";

// Returnerar svaret i lämpligt JSON-format
echo $resultsArray;

?>