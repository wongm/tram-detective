<?php

header('Content-Type: application/json');

require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/melb-tram-fleet/routes.php');
require_once('includes/config.php');
require_once('includes/ServiceRouteData.php');
$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lat != 0 AND lng != 0";
$result = $mysqli->query($tableCheck);

while($row = $result->fetch_assoc())
{
	$melbournetimezone = new DateTimeZone('Australia/Melbourne');
	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['lastupdated']));
	$formattedprediction->setTimezone($melbournetimezone);
	$formatteddate = $formattedprediction->format('d/m/Y H:i');
			
	$tram = new stdClass;
	$tram->id = $row['id'];
	$tram->name = getTramClassAndNumber($row['id']);
	$tram->class = getTramClass($row['id']);
	$tram->lat = $row['lat'];
	$tram->lng = $row['lng'];
	$tram->routeNo = $row['routeNo'];
	$tram->offUsualRoute = ($row['offUsualRoute'] == "1");
	$tram->destination = $row['destination'];
	$tram->direction = $row['direction'];
	$tram->lastupdated = $formatteddate;
	$trams[] = $tram;
}


$tableCheck = "SELECT max(lastupdated) AS `maxlastupdated`, min(lastupdated) AS `minlastupdated` FROM `" . $config['dbName'] . "`.`trams` WHERE lat != 0 AND lng != 0";
$result = $mysqli->query($tableCheck);
$row = $result->fetch_assoc();

$formattedprediction = new DateTime();
$formattedprediction->setTimestamp(strtotime($row['maxlastupdated']));
$formattedprediction->setTimezone($melbournetimezone);	
$maxlastupdated = $formattedprediction->format('d/m/Y H:i');

$formattedprediction = new DateTime();
$formattedprediction->setTimestamp(strtotime($row['minlastupdated']));
$formattedprediction->setTimezone($melbournetimezone);
$minlastupdated = $formattedprediction->format('d/m/Y H:i');

$data['trams'] = $trams;
$data['maxlastupdated'] = $maxlastupdated;
$data['minlastupdated'] = $minlastupdated;
	
echo json_encode($data);

?>

