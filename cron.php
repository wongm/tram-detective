<?php
require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/melb-tram-fleet/routes.php');
require_once('includes/config.php');
require_once('includes/ServiceRouteData.php');

if (!isset($_GET['token']) || $_GET['token'] != $config['cron'])
	die();

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

error_reporting(E_ALL); 
ini_set('display_errors', 1);

$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lastupdated < (NOW() - INTERVAL 10 MINUTE) ORDER BY lastupdated ASC LIMIT 0, 20";
$result = $mysqli->query($tableCheck);

if ($result === false)
{
	echo "Initialise database";
	die();
}

while($row = $result->fetch_assoc())
{
	$tramNumber = $row['id'];
	
	$serviceData = new ServiceRouteData($tramNumber, getTramClass($tramNumber), true);
	$currentLat = $serviceData->currentLat;
	$currentLon = $serviceData->currentLon;
	$routeNo = (int)$serviceData->routeNo;
	$offUsualRoute = $serviceData->offUsualRoute;
	$destination = $serviceData->destination;
	$direction = $serviceData->direction;
	
	if ($currentLat != "" && $currentLon != "")
	{
		if ($offUsualRoute != '1')
		{
			$offUsualRoute = 0;
		}
		
		$tableCheck = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = " . $currentLat . ", `lng` = " . $currentLon . ", `lastupdated` = NOW(), `lastservice` = NOW(), `routeNo` = " . $routeNo . ", `offUsualRoute` = " . $offUsualRoute . ", `destination` = '" . $destination . "', `direction` = '" . $direction . "' WHERE id = " . $tramNumber;
		$result3 = $mysqli->query($tableCheck);
		$tableCheck = "INSERT INTO `" . $config['dbName'] . "`.`trams_history` (`tramid`, `lat`, `lng`, `sighting` , `routeNo`, `offUsualRoute`, `destination`, `direction`) VALUES (" . $tramNumber . ", " . $currentLat . ", " . $currentLon . ", NOW(), '" . $routeNo . "', " . $offUsualRoute . ", '" . $destination . "', '" . $direction . "')";
		$result2 = $mysqli->query($tableCheck);
		$type = "LOCATION";
	}
	else
	{
		$tableCheck = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = 0, `lng` = 0, `lastupdated` = NOW() WHERE id = " . $tramNumber;
		$result2 = $mysqli->query($tableCheck);
		$type = "DATE";
	}
	echo "Updated $tramNumber $type<BR>";
}

?>