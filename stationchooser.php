<?php
require_once("utilities.php");

$from = $_GET["from"];
$to = $_GET["to"];

// Hämtar stationsförslag från Skånetrafiken utifrån det som användaren
// har skrivit in. Returnerar sedan <option>-element med dessa förslag.
function getOptions($search) {

	$searchResults = getStations($search);

	$options = "";
	$name = "";
	$id = "";
	$stationInfo = "";
	for ($i = 0 ; $i < count($searchResults) ; $i++) {

		$name = $searchResults[$i]->Name;
		$id = $searchResults[$i]->Id;
		$stationInfo = '{"name":"' . $name . '","pointId":' . $id . '}';

		$options = $options . "<option value='$stationInfo'>" . $name . "</option>\n";
	}

	return $options;
}

$fromOptions = getOptions($from);
$toOptions = getOptions($to);

?>

<!DOCTYPE html>
<html lang="sv">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Välj stationer</title>
		<link rel="stylesheet" href="themes/kontrast.min.css" />
		<link rel="stylesheet" href="themes/jquery.mobile.icons.min.css" />
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.2/jquery.mobile.structure-1.4.2.min.css" />
		<link rel="stylesheet" href="styles/resplan.css" />
		<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.js"></script>
	</head>
	<body>
		<div data-role="page" id="stationchooser"> <!-- För jQuery Mobile -->
			<div data-role="header"> <!-- För jQuery Mobile -->
				<h1>Res nu Skåne</h1>
			</div>
			<div data-role="content" class="ui-content"> <!-- För jQuery Mobile -->
				<form action="departures.php" method="get">
					<label for="from_alts">Från:</label>
					<select id="from_alts" name="from">
						<?php echo $fromOptions; ?>
					</select>
					<label for="to_alts">Till:</label>
					<select id="to_alts" name="to">
						<?php echo $toOptions; ?>
					</select>
					<button id="departure_btn" class="ui-btn ui-corner-all ui-shadow">Visa avgångar</button>
				</form>
				<a href="stations.php?no_redirect=true" id="journey_btn" class="ui-btn ui-corner-all ui-shadow">Ny sökning</a>
			</div>
		</div>
	</body>
</html>