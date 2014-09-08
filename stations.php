<?php

// Länkar direkt till sidan med avgångar om det finns en cookie
// sedan tidigare med stationsinformation förutsatt att parametern
// no_redirect inte är satt.
if (isset($_COOKIE["stations"])) {

	if (!isset($_GET["no_redirect"])) {

		// Skickar vidare användaren direkt till avgångarna
		header("Location: departures.php");

	} else {

		// Ska använda informationen från cookie för att sätta
		// startvärden på input-elementen.
		$cookieObj = json_decode($_COOKIE["stations"]);
		$fromObj = $cookieObj[0];
		$toObj = $cookieObj[1];
	}
}

?>

<!DOCTYPE html>
<html lang="sv">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Ange stationer du vill resa mellan</title>
		<link rel="stylesheet" href="themes/kontrast.min.css" />
		<link rel="stylesheet" href="themes/jquery.mobile.icons.min.css" />
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.2/jquery.mobile.structure-1.4.2.min.css" />
		<link rel="stylesheet" href="styles/resplan.css" />
		<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.js"></script>
		<script src="scripts/stations.js"></script>
		<script src="scripts/departures.js"></script>
	</head>
	<body>
		<div data-role="page" id="stationchooser"> <!-- För jQuery Mobile -->
			<div data-role="header"> <!-- För jQuery Mobile -->
				<h1>Res nu Skåne</h1>
			</div>
			<div data-role="content" class="ui-content"> <!-- För jQuery Mobile -->
				<form title="Ange från- och till-station" class="ui-filterable" action="stationchooser.php" method="get">
					<label for="first_input">Från:</label>
					<input type="text" id="first_input" name="from" data-type="search" placeholder="Ange station/hållplats" required 
						<?php
							// Sätter initiala värden från cookie-information
							if (isset($cookieObj)) {
								echo "data-station='" . json_encode($fromObj) . "' value='" . $fromObj->name . "'";
							}
						?>
					>
					<ul title="Sökresultat: Från-hållplatser" data-role="listview" data-filter="false" id="firststation" data-input="#first_input" data-inset="true"></ul>
					<label for="second_input">Till:</label>
					<input type="text" id="second_input" name="to" data-type="search" placeholder="Ange station/hållplats" required
						<?php
							// Sätter initiala värden från cookie-information
							if (isset($cookieObj)) {
								echo "data-station='" . json_encode($toObj) . "' value='" . $toObj->name . "'";
							}
						?>
					>
					<ul title="Sökresultat: Till-hållplatser" data-role="listview" data-filter="false" id="secondstation" data-input="#second_input" data-inset="true"></ul>
					<button id="departure_btn" class="ui-btn ui-corner-all ui-shadow">Visa avgångar</button>
				</form>
			</div>
		</div>
	</body>
</html>