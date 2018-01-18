<?php

header('Content-Type: application/json');

require_once('includes/melb-tram-fleet/trams.php');
require_once('includes/melb-tram-routes/routes.php');
require_once('includes/config.php');
require_once('includes/ServiceRouteData.php');

$classes = 'z1,z2,z3,a1,a2,b1,b2,c,c2,d1,d2,e';
$forceRefresh = false;

if (isset($_GET['class']))
{
	$classes = $_GET['class'];
}

if (isset($_GET['forceRefresh']))
{
	$forceRefresh = (md5($_GET['refresh']) == 'a36e5a40ee3db56d0120ed0201c2c72f');
}

$trams = array();

foreach(split(',', $classes) as $class)
{
	foreach($melbourne_trams[strtoupper($class)] as $tramNumber)
	{
		$serviceData = new ServiceRouteData($tramNumber, getTramClass($tramNumber), $forceRefresh);
	
		if((strlen($serviceData->error) == 0) && isset($serviceData->currentLat) && isset($serviceData->currentLon))
		{
			$tram = new stdClass;
			$tram->id = $tramNumber;
			$tram->name = getTramClassAndNumber($tramNumber);
			$tram->class = getTramClass($tramNumber);
			$tram->lat = $serviceData->currentLat;
			$tram->lng = $serviceData->currentLon;
			$tram->routeNo = (int)$serviceData->routeNo;
			$tram->destination = $serviceData->destination;
			$tram->direction = $serviceData->direction;
			$trams[] = $tram;
		}
	}
}

echo json_encode($trams);

?>