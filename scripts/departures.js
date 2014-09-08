"use strict";

// Inställningar för de automatiska uppdateringarna (i millisekunder)
Commuter.timeBetweenUpdates = 60 * 1000;
Commuter.updatesMaxTime = 3 * 60 * 1000;


$(document).on("pagecreate", "#departure_page", function createSetup() {

	// Sätter händelsehanterare för knappen med vilken man byter riktning
	$("#changedir").off("tap keypress").on("tap keypress", Commuter.changeDirection);

	// Sätter händelsehanterare för knappen till stationssidan
	$("#new_journey_btn").off("tap keypress").on("tap keypress", function changeHandler(event) {

		if (event.type === "keypress" && event.which !== 13) {

			// Om man inte har tryckt på Enter så ska inget göras
			return;

		} else {
			// Byter till sidan där man väljer stationer
			$.mobile.pageContainer.pagecontainer("change", "stations.php?no_redirect=true");
		}
	});
});


$(document).on("pagebeforeshow", "#departure_page", function pageInit() {

	var stations;
	var $from = $("#from");
	var $to = $("#to");

	// Rensar bort eventuell reseinformation från en tidigare sökning
	// (syns annars innan man har fått aktuell information från servern)
//	Commuter.clearTimeToDeparture();
//	$(".resvag").empty();

	// Tar bort texten att man behöver ladda om sidan för att tiderna
	// ska uppdateras, när JS är på så görs det automatiskt
	$("#reload_info").remove();

	// Startar automatisk uppdatering av avgångar
	Commuter.startUpdates($from, $to);

});


// Då sidan inte längre visas så slutar man att hämta
// uppdateringar från servern.
$(document).on("pagebeforehide", "#departure_page", function stopUpdates() {

	clearInterval(Commuter.intervalId);
	clearTimeout(Commuter.timeoutId);

});


// Startar igång trådarna som hanterar hämtning av ny avgångsinformation
// samt att hämtningen avslutas efter ett antal minuter (för att inte
// belasta tjänsten i onödan).
Commuter.startUpdates = function($from, $to) {

	// Hanterar att startUpdates skulle kunna anropas flera gånger, men man vill
	// inte att det ska resultera i många "trådar" utan bara en.
	// (vid första anropet existerar inte intervalId och timeoutId, men det ignoreras
	// av clearInterval och clearTimeout).
	clearInterval(Commuter.intervalId);
	clearTimeout(Commuter.timeoutId);

	//Uppdaterar avgångarna enligt timeBetweenUpdates
	Commuter.intervalId = setInterval(function updateDepartures() {
		Commuter.getDepartures($from.attr("data-point-id"), $to.attr("data-point-id"));
	}, Commuter.timeBetweenUpdates);

	// Efter ett antal minuters uppdateringar så frågas om man vill fortsätta att
	// uppdatera avgångarna, så att man inte belastar server/tjänst i onödan.
	Commuter.timeoutId = setTimeout(function updateDialog() {
		clearInterval(Commuter.intervalId);
		if (confirm("Vill du fortsätta uppdatera avgångarna?")) {

			Commuter.startUpdates($from, $to);

		} else {

			// Om man väljer att avsluta uppdateringarna så tas informationen
			// om hur lång tid det är till avgång bort, men resvägarna behålls.
			Commuter.clearTimeToDeparture();
		}
	}, Commuter.updatesMaxTime);
};


// Byter riktning på resan, dvs. från-station blir till-station
// och vice versa.
Commuter.changeDirection = function() {

	if (event.type === "keypress" && event.which !== 13) {

			// Om man inte har tryckt på Enter så ska inget göras
			return;
	} else {

		var $from = $("#from");
		var $to = $("#to");

		// Tar ut värdena ur från- och till-elementen
		var oldFromText = $from.text();
		var oldFromId = $from.attr("data-point-id");
		var oldToText = $to.text();
		var oldToId = $to.attr("data-point-id");

		// Byter värdena så att från-station blir till-station och vice versa
		$from.find("strong").text(oldToText);
		$from.attr("data-point-id", oldToId);

		$to.find("strong").text(oldFromText);
		$to.attr("data-point-id", oldFromId);

		// Gör en initial uppdatering av avgångar
		Commuter.getDepartures($from.attr("data-point-id"), $to.attr("data-point-id"));

		//Startar uppdateringar
		Commuter.startUpdates($from, $to);
	}
};


// Hämtar information om avgångar med ett Ajax-anrop till servern
Commuter.getDepartures = function(fromId, toId) {

	$.getJSON("departures_search.php", {from: fromId, to: toId}).done(Commuter.updateTimeTable).
	fail(function errorLoading() { Commuter.displayError("Avgångarna kunde inte uppdateras!"); });

};


// Uppdaterar tidtabellsinformationen
Commuter.updateTimeTable = function(timeTableArray) {

	var $collapsibles = $("div[data-role='collapsible']");

	// Rensar bort eventuellt felmeddelande
	Commuter.displayError("");

	// Uppdaterar det <a>-element som jQuery Mobile skapat med ny info,
	// är där man visar antalet minuter till avgång.
	$collapsibles.find("a").each(function aEnhance(index) { 

		$(this).html(timeTableArray[index].header).
		append("<span class='ui-collapsible-heading-status'> Klicka för att expandera</span>"); });

	// Uppdaterar reseinformation, dvs. resvägen som kommer som "färdig" HTML från servern.
	$collapsibles.find("div[title='Resväg']").each(function insertTimeTable(index) { $(this).html(timeTableArray[index].journeyInfo); });

};


// Hanterar visandet av felmeddelanden. Skickas en tom sträng 
// som argument så tas ev. felmeddelande och tillhörande element bort.
Commuter.displayError = function(message) {

	var $error = $(".error");

	if (message === "") {

		if ($error.length !== 0) {

			$error.remove();
		}
	} else {

		if ($error.length === 0) {

			$error = $("<p class='error'></p>").prependTo("#departures");			
		}
		$error.html(message);
		Commuter.clearTimeToDeparture();
	}
};


// Rensar bort tid till avgång i gränssnittet t.ex. då man inte 
// har lyckats hämta information om avgångar.
Commuter.clearTimeToDeparture = function() {

	$("div[data-role='collapsible'] a").html("- min");
};