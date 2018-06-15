<?php
require_once('includes/melb-tram-fleet/trams.php');
require_once('includes/config.php');

$pageTitle = "Fleet Tracker";
$pageDescription = "Tracking every tram in Melbourne";
require_once('includes/Header.php');
?>
<link rel="stylesheet" type="text/css" href="css/style.css">
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
	var count = 0;

	var mapDataUrl = "mapdata.php";
	if (getParameterByName('class') != null)
	{
		mapDataUrl += "?class=" + getParameterByName('class');
	}

	// get data and use it
	$.getJSON( mapDataUrl , function( data ) {
		$.each( data.trams, function( index, tram ) {

			if (tramRoutes.length > 0 && ($.inArray(tram.routeNo, tramRoutes) < 0)) { return true; }
			if (tramClasses.length > 0 && ($.inArray(tram.class, tramClasses) < 0)) { return true; }
			if (tramDirection.length > 0 && tram.direction != tramDirection) { return true; }
			
			if (getParameterByName('type') == null || tram.offUsualRoute)
			{
				addMarker(tram.name, tram.lat, tram.lng, tram.routeNo, tram.destination, tram.direction, tram.offUsualRoute, tram.lastupdated);
				count++;
			}
		});
		
		if (count == 0)
		{
			$('#map-canvas').html('<div class="alert alert-warning">No trams found!</div>');
		}
		else
		{
			map.fitBounds(bounds);
		}
		
		$('#updated').html('<div class="lightbox">Data between ' + data.minlastupdated + ' and ' + data.maxlastupdated + '</div>');
	});
}

function addMarker(tram, lat, lng, routeNo, destination, direction, offUsualRoute, lastupdated) {
	var icon = offUsualRoute ? 'red.png' : ((direction=='down') ? 'orange.png' : 'blue.png');
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
		infowindow.setContent('<div class="lightbox">' + content + '<br>Route ' + routeNo + ' towards ' + destination + '<br>Updated ' + lastupdated + '</div>');
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
<style>
body {
     overflow: hidden;
}
h1 {
}
.container-fluid {
	padding: 0;
}
#footer {
    position: absolute;
    bottom: 25px;
    padding-left: 20px;
}
.alert {
	margin-left: 10px;
}
</style>
<div id="map-canvas"></div>
<div id="topLink">
	<a href="/">&larr; Go back home</a>
</div>
<span id="updated"></span>
<?php
require_once('includes/Footer.php');
?>
