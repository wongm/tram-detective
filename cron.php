<?php
require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/melb-tram-fleet/routes.php');
require_once('includes/config.php');
require_once('includes/ServiceRouteData.php');

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

error_reporting(E_ALL); 
ini_set('display_errors', 1);

$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lastupdated < (NOW() - INTERVAL 10 MINUTE) ORDER BY lastupdated ASC LIMIT 0, 20";
$result = $mysqli->query($tableCheck);

while($row = $result->fetch_assoc())
{
	$tramNumber = $row['id'];
	
	$serviceData = new ServiceRouteData($tramNumber, getTramClass($tramNumber), true);
	$currentLat = $serviceData->currentLat;
	$currentLon = $serviceData->currentLon;
	
	if ($currentLat != "" && $currentLon != "")
	{
		$tableCheck = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = " . $currentLat . ", `lng` = " . $currentLon . ", `lastupdated` = NOW() WHERE id = " . $tramNumber;
		$type = "LOCATION";
	}
	else
	{
		$tableCheck = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lastupdated` = NOW() WHERE id = " . $tramNumber;
		$type = "DATE";
	}
	$result2 = $mysqli->query($tableCheck);
	echo "Updated $tramNumber $type<BR>";
}

?>