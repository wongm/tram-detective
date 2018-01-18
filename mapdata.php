<?php

header('Content-Type: application/json');

require_once('includes/MelbourneTrams.php');
require_once('includes/config.php');
require_once('includes/ServiceRouteData.php');

//temp hack
//require_once('upgradephp/upgrade.php');

$classes = $_GET['class'];
$forceRefresh = (md5($_GET['refresh']) == 'a36e5a40ee3db56d0120ed0201c2c72f');

if (strlen($classes) == 0)
{
	$classes = 'z1,z2,z3,a1,a2,b1,b2,c,c2,d1,d2,e';
}

$trams = array();

foreach(split(',', $classes) as $class)
{
	foreach($melbourne_trams[strtoupper($class)] as $vehicleNo)
	{
		$serviceData = new ServiceRouteData($vehicleNo, $forceRefresh);
	
		if((strlen($serviceData->error) == 0) && isset($serviceData->currentLat) && isset($serviceData->currentLon))
		{
			$tram = new stdClass;
			$tram->id = $vehicleNo;
			$tram->name = getTramClassAndNumber($vehicleNo);
			$tram->class = getTramClass($vehicleNo);
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