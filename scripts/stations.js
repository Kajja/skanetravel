"use strict";

// Objekt som håller metoder och variabler, för att inte stöka till den
// globala namnrymden
var Commuter = {};

// Fix för att hantera jQuery Mobile:s cachning. Första "page" som användaren
// hamnar på kommer alltid finnas kvar i DOM:n med ett data-url attribut
// som är lika med page-elementets id. Detta blir ett problem om man från
// en annan sida navigerar till den första sidan igen, då känner inte jQM
// igen att detta är den första sidan utan skapar ett nytt page-element
// i DOM:n som får ett annat data-url attribut men med samma id som det 
// page-element som redan finns i DOM:n (jQM använder data-url attributen 
// på sidorna för att indentifiera om en sida redan finns i DOM:n). Med
// flera "pages" med samma id får man sedan problem när man ska binda 
// händelsehanterare till en "page" utifrån dess id.
// Fixen tar bort det första page-element som skapades i DOM:n.
$(document).on("pagecontainerhide", function removeInitPage() {

	$.mobile.firstPage.remove();
});


// Uppsättning av diverse händelsehanterade m.m.
// [Gör .off() innan .on() pga. att jQuery Mobile event triggas
// lite ointuitivt och man riskerar att registrera flera händelsehanterade.
// Delegerar även händelsen till "document" för att skriptet körs bra första gången
// som sidan laddas och ev. "pages" behöver då inte existera i DOM:n ännu (JQuery
// Mobile hämtar nya sidor med Ajax-anrop, och ev. skript i HTML-header körs inte för
// webbsidor som hämtas efter den första).]
$(document).on("pagecreate", "#stationchooser", function(event) {

	var $first = $("#first_input"); 	// Fält där man anger Från-station
	var $second = $("#second_input"); 	// Fält där man anger Till-station
	var $ulFirst = $("#firststation"); 	// Lista som populeras med sökresultat
	var $ulSecond = $("#secondstation");// Lista som populeras med sökresultat
	var stations;

	// Händelsehanterare för när man skriver i textrutan för Från-station
	$first.off("input").on("input", (function() {
										var delayer = Commuter.delayer();
										return function(e) {
											delayer(function() { Commuter.getStations(e, $ulFirst); }, 300);
										};
									})()
							);

	// Händelsehanterare för när man skriver i textrutan för Till-station
	$second.off("input").on("input", (function() {
									var delayer = Commuter.delayer();
									return function(e) {
										delayer(function() { Commuter.getStations(e, $ulSecond); }, 300);
									};
								})()
							);

	// Händelsehanterare för när användaren klickar på ett val i listan med 
	// matchande stationer. (Delegering samt endast om event.target var ett li-element)
	$ulFirst.off("tap").on("tap keypress", "li", function(event) { //'keypress' för desktop
		Commuter.setStation(event, $ulFirst);});

	$ulSecond.off("tap").on("tap keypress", "li", function(event) {
		Commuter.setStation(event, $ulSecond);});

	// Rensar bort stationsdata-attribut då man klickar på kryssen i sökrutorna och tar bort ev.
	// sökresultatlista (genom att trigga "input"). Texten i sökrutorna tas bort av jQM.
	$(".ui-input-clear").on("click", function() {
		$(this).prevAll().removeAttr("data-station").trigger("input");
	});

	// Händelsehanterare för formuläret
	$("form").off("submit").on("submit", function(event) {

		// Validerar indata
		if (Commuter.validInput($first, $second)) {
		
			// Länka vidare till sidan med avgångar, skickar med stationsdata
//			$.mobile.changePage("departures.php?from=" + $first.attr("data-station") + 
//				"&to=" + $second.attr("data-station"));

			$(":mobile-pagecontainer").pagecontainer("change", "departures.php?from=" + $first.attr("data-station") + 
				"&to=" + $second.attr("data-station"), {dataUrl: "departure_page"});

			// Måste stoppa eventet iom att nya sidor laddas i samma DOM
			// dvs. man byter egentligen inte sida
			event.preventDefault();

		} else {

			// Ser till så att eventet stoppas (ett annat sätt än ovan)
			return false;
		}
	});
});


// Returnerar en funktion som fördröjer triggande av en callback-funktion. 
// Används vid inmatning i sökfälten så att inte ett Ajax-anrop 
// görs så fort ett tecken fylls i utan först efter att användaren 
// har skrivit klart söksträngen.
Commuter.delayer = function() {

	var timerRef = 0;

	return function(callback, delay) {

		clearTimeout(timerRef);
		timerRef = setTimeout(callback, delay);

	};
};


// Metod som gör ett Ajax-anrop mot en tjänst på servern, med den söksträng som
// användaren har skrivit in det input-fält som är event-target, för att se
// vilka stationer som matchar söksträngen. Minst 3 stycken tecken krävs för att
// sökning ska ske.
Commuter.getStations = function(event, $ul) {

	// Plocka ut värdet från sökfältet
	var text = event.target.value;

	event.preventDefault();

	// Rensar bort gammalt sökresultat
	$ul.empty();

	// Kollar om söksträngen är minst tre tecken lång
	if (text.length > 2) {

		// Skapar ett Ajax-anrop mot servern för att hämta stationer som
		// matchar söksträngen.
		$.getJSON("stations_search.php", {search: text}, function(json) {
			Commuter.parseAjaxResponse(json, $ul);
		});
	}
};


// Metod som hanterar resultatet med stationer från servern,
// dvs. skapar en lista med stationerna och lägger till DOM:n.
Commuter.parseAjaxResponse = function(jsonResponse, $ul) {

	var response = "";
	var $searchField = $($ul.attr("data-input")); //Sökrutan till aktuell ul

	// Skapa <li>-element utifrån svaren på Ajax-anropet
	for (var i = 0 ; i < jsonResponse.length ; i++) {

		response += "<li tabindex='0' data-station='" + JSON.stringify(jsonResponse[i]) + "'>" + jsonResponse[i].name + "</li>";		
	}

	$ul.append(response);

	// Uppdatera jQuery Mobile layouten
	$ul.listview("refresh");
	$ul.trigger("updatelayout");

	// Scrolla fönstret så att så många av sökträffarna som möjligt blir synliga
	// annars kan de döljas av tangentbordet på mobilens skärm.
	$(window).scrollTop($searchField.offset().top - 5);

};


// Anropas när man klickar på ett av de dynamiskt skapade li-lementen. 
// Då tas texten från detta och läggs i sökrutan och listan tömms 
// sedan på alla element. Använder att event från li-elementen bubblar
// upp till ul-elementet.
Commuter.setStation = function(event, $ul) {

	if (event.type === "keypress" && event.which !== 13) { // För desktop

		// Det är endast om man trycker på Enter som man väljer
		// en station i listan, annars så ska inget göras.
		return;

	} else {

		var $searchField = $($ul.attr("data-input")); // Sökrutan till aktuell ul
		var $target = $(event.target); // li-elementet där händelsen initierades

		// Ser till att eventet inte bubblar vidare
		event.stopPropagation();

		// Vid ett "tap" så skapas även ett click-event, förhindrar detta med att göra
		// event.preventDefault(). Dock ej en helt felfri lösning.
		event.preventDefault();

		// Fyller i stationsnamnet i sökrutan
		$searchField.val($target.text());

		// Lagrar stationsid i ett attribut "data-station"
		$searchField.attr("data-station", $target.attr("data-station"));

		// Tar bort listan med sökträffar
		$ul.empty();

		// Sätter fokus på sökrutan
		$searchField.focus();
	}
};


// Validerar informationen i input-fälten
// Får med referenser till jQuery-objekt som respresenterar input-fälten ($first och $second).
Commuter.validInput = function($first, $second) {

	var valid = true;
	var errorMsg = "Felmeddelande:\n";

	if ($first.val().length === 0) {

		errorMsg = errorMsg + "Från-station saknas\n";
		valid = false;
	}

	if ($second.val().length === 0) {

		errorMsg = errorMsg + "Till-station saknas\n";
		valid = false;
	}	

	// Kontrollerar så att båda fälten är ifyllda med
	// "riktiga" stationer, dvs. att användaren har valt
	// en station från de alternativ som kommer upp i listan.
	if (!($first.attr("data-station") && $second.attr("data-station"))) {

		errorMsg = errorMsg + "Du måste välja stationer från alternativen"
		valid = false;
	}

	// Skriver ut ev. felmeddelande
	if (!valid) {

		alert(errorMsg);
	}

	return valid;
};