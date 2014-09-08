<?php
require_once("utilities.php");

// Plockar information om från- och tillstation, ska finnas antingen
// i URL:n eller i cookie.
if (isset($_GET["from"]) && isset($_GET["to"])) {

	$from = $_GET["from"];
	$to = $_GET["to"];

	$fromObj = json_decode($from);
	$toObj = json_decode($to);

	// Uppdaterar cookie, eller skapar en om första gången, på formatet
	// [{"name":"Helsingborg Halalid","pointId":"83023"},{"name":"Skanör centrum","pointId":"33025"}]
	setcookie("stations", "[" . $from . "," . $to . "]", time() + 60 * 60 * 24 * 30); //Livslängd cookie = 30 dagar

} else {

	if (isset($_COOKIE["stations"])) {

		// Plockar ut stationsinfo från cookie
		$cookieObj = json_decode($_COOKIE["stations"]);
		$fromObj = $cookieObj[0];
		$toObj = $cookieObj[1];

	} else {
		// Ingen stationsinformation, varken i URL eller cookie, 
		// skickar användaren till sidan där man väljer stationer.
		header("Location: stations.php");		
	}
}

// Antalet reseförslag man vill ha tillbaka vid sökning mot Skånetrafiken
$noOfJourneys = 3;

// Hämtar reseförslag från Skånetrafiken
$nextDepartures = json_decode(getDepartures($fromObj->pointId, $toObj->pointId, $noOfJourneys));

?>

<!DOCTYPE html>
<html lang="sv">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Avgångar mellan valda stationer</title>
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
		<div data-role="page" id="departure_page"> <!-- För jQuery Mobile -->
			<div data-role="header"> <!-- För jQuery Mobile -->
				<h1>Res nu Skåne</h1>
			</div>
			<div data-role="content" class="ui-content"> <!-- För jQuery Mobile -->
				<div id="journey">
					<div id="stations">
						<table id="stations_table">
							<tr>
								<td><span>Från:</span></td>
								<td><span id="from" title="Från-station" data-point-id="<?php echo $fromObj->pointId; ?>"><strong><?php echo $fromObj->name; ?></strong></span></td>
								<td rowspan="2"><button title="Byt riktning" id="changedir" class="ui-btn ui-btn-inline ui-corner-all ui-mini ui-shadow" ><img src="images/pilar.gif" alt="Byt riktning"></button></td>
							</tr>
							<tr>
								<td><span>Till:</span></td>
								<td><span id="to" title="Till-station" data-point-id="<?php echo $toObj->pointId; ?>"><strong><?php echo $toObj->name; ?></strong></span></td>
							</tr>
						</table>
					</div>
				</div>
				<div id="departures">
					<p>Avgångar:</p>
					<p id="reload_info">(Uppdaterade <?php echo date("H:i", time()); ?>, ladda om sidan för att uppdatera)</p>
					<div data-role="collapsibleset" data-iconpos="right" id="timetable">

					<?php
						// Lägger in reseförslagen som <div>-element med data-role="collapsible" (för jQuery Mobile)
						$collapsibles = "";
						for ($i = 0 ; $i < $noOfJourneys ; $i++) {
							$alt = $i + 1;
							$collapsibles = $collapsibles . 
							"<div data-role='collapsible'>" . 
								"<h3 title='Alternativ $alt'>" . $nextDepartures[$i]->header . "</h3>" .
								"<div class = 'resvag' title='Resväg'>" . $nextDepartures[$i]->journeyInfo . "</div>" .
							"</div>";
						}
						echo $collapsibles;
					?>

					</div>
				</div>
				<a href="stations.php?no_redirect=true" id="new_journey_btn" class="ui-btn">Sök ny resa</a>
			</div>
		</div>
	</body>
</html>