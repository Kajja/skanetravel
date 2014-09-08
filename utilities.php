<?php

//
//
function getStations($search) {

	//Initiera cURL-session
	$ch = curl_init("http://www.labs.skanetrafiken.se/v2.2/querystation.asp?inpPointfr=" . urlencode($search));

	//Anger inställningar för överföringen
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	//Exekvera sessionen
	$data = curl_exec($ch);

	//Avsluta sessionen
	curl_close($ch);

	//////// Hanterar resultatet från Skånetrafiken

	//Skapar ett SimpleXML-objekt av strängen med XML-data
	$soapXML = simplexml_load_string($data);

	//"Rensar" xml-resultatet från soap-namespace och andra namespaces
	$traficXML = $soapXML->children("http://schemas.xmlsoap.org/soap/envelope/")->children("http://www.etis.fskab.se/v1.0/ETISws");

	//Plockar ut stationerna, som matchade sökningen, ur XML-objektet
	$pointsArray = $traficXML->GetStartEndPointResponse->GetStartEndPointResult->StartPoints->Point;

	return $pointsArray;

//	return "Test stations";
}


// Funktion för att avgöra vilken ikon som ska visas dvs. buss, tåg, eller färja.
// Returnerar en <img>-elementsträng.
function iconChooser($transportMode) {

	//Array som mappar transporttyper till transport-ikoner
	$icons = array(
		array("mode" => "Stadsbuss", "file" => "stadsbuss.png", "alt" => "Stadsbuss"),
		array("mode" => "Regionbuss", "file" => "regionbuss.png", "alt" => "Regionbuss"),
		array("mode" => "Tåg", "file" => "tag.png", "alt" => "Tåg"),
		array("mode" => "Färjeförbindelse", "file" => "farja.png", "alt" => "Färja"),
		array("mode" => "Buss, kommersiell", "file" => "buss.png", "alt" => "Buss"),
		array("mode" => "Gång", "file" => "ga.png", "alt" => "Gå")
		);

	$img = "<img src='images/";

	foreach ($icons as $key => $value) {
		if ($transportMode == $value["mode"]) {

			$img = $img . $value["file"] . "' alt='" . $value["alt"] . "'/>";
			break;
		}
	}

	//-->Borde ha felhantering här också
	return $img;
}

// Funktion som hanterar att Stadbussar har ett lokalt nummer ex. 2 men att det
// "officiella" numret egentligen kan vara 602.
function localBusTranslation($routeSection) {

	if ($routeSection->Line->TransportModeName == "Stadsbuss") {

		$lineNo = $routeSection->Line->Name;

	} else {

		$lineNo = $routeSection->Line->No;
	}

	return $lineNo;
}

// Funktion som gör om från minuter till timmar och minuter på formatet
// "x tim y min".
function timeConverter($time) {

		//Om det är mer än 60 minuter tills avgången
	if ($time > 60) {

		$time = intval($time/60) . " tim " . $time%60 . " min";

	} else {

		$time = $time . " min";
	}

	return $time;

}

// Funktion som gör om från minuter till timmar och minuter på formatet
// "xx:yy".
function timeConverter2($time) {

	$hours = "00";
	$mins = $time%60;

		//Om det är 60 minuter eller mer tills avgången
	if ($time >= 60) {

		$hours = intval($time/60);
		if ($hours < 10) {

			$hours = "0" . $hours;
		}
	}
	if ($mins < 10) {

		$mins = "0" . $mins;
	}
	return $hours . ":" . $mins;
}


//
function getDepartures($fromPoint, $toPoint, $noOfJourneys) {

	//---- Gör anrop mot Skånetrafiken ----//
	
	$ch = curl_init("http://www.labs.skanetrafiken.se/v2.2/resultspage.asp?cmdaction=next&selPointFr=|" . 
		$fromPoint . "|0&selPointTo=|" . $toPoint . "|0&NoOf=" . $noOfJourneys);

	// Anger inställningar för överföringen
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	$data = curl_exec($ch);
	curl_close($ch);

	//---- Hanterar resultatet från Skånetrafiken ----//

	// Skapar ett SimpleXML-objekt av strängen med XML-data
	$soapXML = simplexml_load_string($data);

	// "Rensar" xml-resultatet från soap-namespace och andra namespaces
	$traficXML = $soapXML->children("http://schemas.xmlsoap.org/soap/envelope/")->children("http://www.etis.fskab.se/v1.0/ETISws");

	// Plockar ut reseförslagen
	$journeysArray = $traficXML->GetJourneyResponse->GetJourneyResult->Journeys->Journey;

	// Hämtar aktuellt tid från servern (Unix-tid)
	$time = time();

	// Anger tidszon så att strtotime() ska räkna om till rätt Unix-tid.
	date_default_timezone_set("Europe/Stockholm");

	// Börjar bygga den sträng, som returneras till klienten, som kommer innehålla 
	// information om de reseföslag som man har fått från Skånetrafiken. Är på
	// JSON-format.
	$resultsArray ="[";

	// Går igenom varje reseförslag och plockar ut information
	for ($i = 0 ; $i < $noOfJourneys ; $i++) {

		// Beräknar restid
		$journeyTime = timeConverter2((strtotime($journeysArray[$i]->ArrDateTime) - strtotime($journeysArray[$i]->DepDateTime))/60);

		// Plockar ut de delsträckor som resan består av
		$route = $journeysArray[$i]->RouteLinks->RouteLink;

		// Beräknar antalet minuter till avgång (det är den första
		// delen av resan som är intresseant dvs. $route[0])
		$timeLeft = intval((strtotime($route[0]->DepDateTime) - $time)/60);

		// Korrigerar för ev. förändring mot tidtabell t.ex. på grund av försening
		if ($route[0]->RealTime->RealTimeInfo) {

			$deviation = $route[0]->RealTime->RealTimeInfo->DepTimeDeviation;
			$timeLeft += $deviation;
		}

		// Gör om från minuter till formatet "x tim y min"
		$timeLeft = timeConverter($timeLeft);

		// Skapar strängen som ska innehålla resvägsinfon
		$routeInfo = "";

		// Går igenom alla delsträckor av resan
		for ($j = 0 ; $j < count($route) ; $j++) {

			// Avgångs- och ankomsttider för en viss delsträcka av resan
			$departure = date("H:i", strtotime($route[$j]->DepDateTime));
			$arrival = date("H:i", strtotime($route[$j]->ArrDateTime));

			// Skapar en HTML-tabell för denna delsträcka av resan
			$routeInfo = $routeInfo . "<div title='Delsträcka " . ($j+1) . "'>";
			$routeInfo = $routeInfo . 
				"<table><tbody><tr><td></td><td>" . iconChooser($route[$j]->Line->TransportModeName) .
				" " . $route[$j]->Line->LineTypeName . " " . localBusTranslation($route[$j]) . ":</td></tr>" . 
				"<tr><td><time>" . $departure . "</time></td><td>" . $route[$j]->From->Name . "</td></tr>" .
				"<tr><td><time>" . $arrival . "</time></td><td>" . $route[$j]->To->Name . "</td></tr></tbody></table></div>";
		}

		// Svar i JSON på formatet: "header: ... , journeyInfo: ..."
		$resultsArray = $resultsArray . '{"header":"' . $timeLeft . '<small>Restid: ' . $journeyTime .'</small>",' .
		'"journeyInfo":"' . $routeInfo . '"},';

	}

	// Justering, byter det sista kommatecknet mot en hakparentes.
	$resultsArray = substr($resultsArray, 0, -1) . "]";

	return $resultsArray;
}

?>