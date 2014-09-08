<?php

	require_once("utilities.php");

	
	// Plockar ut stationer från anropet
	$fromPoint = $_GET["from"];
	$toPoint = $_GET["to"];

	// Antalet reseförslag man vill ha tillbaka
	$noOfJourneys = 3;

	// Returnerar svaret (som är i JSON-format)
	echo getDepartures($fromPoint, $toPoint, $noOfJourneys);	

?>