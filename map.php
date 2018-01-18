<?php
require_once('includes/melb-tram-fleet/trams.php');
require_once('includes/config.php');

$pageTitle = "Fleet Tracker";
$pageDescription = "Tracking every tram in Melbourne";
require_once('includes/Header.php');
?>
<style type="text/css">
h1 { display: none; }
html { height: 100% }
body { height: 100%; margin: 0; padding: 0 }
#map-canvas { height: 100% }
</style>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $config['googleapi'] ?>&sensor=false"></script>
<script type="text/javascript">
var map;
var markers = [];
var infowindows = [];
var latlngs = [];

var bounds = new google.maps.LatLngBounds();
var infowindow = new google.maps.InfoWindow();

var tramRoutes = new Array( 109, 57 );
var tramClasses = new Array( 'C', 'Z3' );
var tramDirection = 'up';

var tramRoutes = new Array(  );
var tramClasses = new Array(  );
var tramDirection = '';

function initialize() {
    map = new google.maps.Map(document.getElementById("map-canvas"));

	var mapDataUrl = "mapdata.php";
    if (getParameterByName('class') != null)
        mapDataUrl += "?class=" + getParameterByName('class');

    // get data and use it
	$.getJSON( mapDataUrl , function( data ) {
		$.each( data, function( index, tram ) {
			// apply filtering
			console.log($.inArray(tram.routeNo, tramRoutes));

			if (tramRoutes.length > 0 && ($.inArray(tram.routeNo, tramRoutes) < 0)) { return true; }
			if (tramClasses.length > 0 && ($.inArray(tram.class, tramClasses) < 0)) { return true; }
			if (tramDirection.length > 0 && tram.direction != tramDirection) { return true; }

			addMarker(tram.name, tram.lat, tram.lng, tram.routeNo, tram.destination, tram.direction);
		});
		map.fitBounds(bounds);
	});
}

function addMarker(tram, lat, lng, routeNo, destination, direction) {
	var icon = (direction=='down') ? 'orange.png' : 'red.png';
	var content = 'Tram ' + tram;
	latlngs[tram] = new google.maps.LatLng(lat, lng);
	bounds.extend(latlngs[tram]);
	markers[tram] = new google.maps.Marker({
    	position: latlngs[tram],
    	map: map,
    	icon: 'http://maps.google.com/mapfiles/ms/micons/' + icon,
    	title: content
	});
	google.maps.event.addListener(markers[tram], 'click', function() {
		infowindow.setContent('<div class="lightbox">' + content + '<br>Route ' + routeNo + ' towards ' + destination + '</div>');
		infowindow.open(map, this);
	});
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

google.maps.event.addDomListener(window, 'load', initialize);

$(document).ready(function() {

});
</script>
<div id="map-canvas"></div>
<?php
require_once('includes/Footer.php');
?>
