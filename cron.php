<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$timeStart = microtime(true);

require_once(__DIR__.'/includes/melb-tram-fleet/functions.php');
require_once(__DIR__.'/includes/melb-tram-fleet/routes.php');
require_once(__DIR__.'/includes/config.php');
require_once(__DIR__.'/includes/functions.php');
require_once(__DIR__.'/includes/ServiceRouteData.php');

define('UPDATE_MINUTES', 9);
define('BATCH_SIZE', 100);

// look for command line parameters and convert them to same ones seen from HTTP GET
$readMode = !isset($_GET['update']);
$separator = "<br>";
if (isset($argv))
{
	$readMode = false;
	$separator = "\r\n";
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

// security check to make sure randoms can't hit this page
if (!isset($_GET['token']) || $_GET['token'] != $config['cron'])
{
	die();
}

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

$sqlWhere = "lastupdated < (NOW() - INTERVAL " . UPDATE_MINUTES . " MINUTE)";
if (isset($_GET['odds']) && is_numeric($_GET['odds']))
{
	$sqlWhere .= " AND MOD(id, 2) = " . $_GET['odds'] . " AND LENGTH(id) = " . $_GET['length'];
}

$sqlLimit = "LIMIT 0, " . BATCH_SIZE;
if ($readMode)
{
	echo "Status check...";
	$sqlLimit = "";
}

$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE $sqlWhere ORDER BY lastupdated ASC $sqlLimit";
$result = $mysqli->query($tableCheck);

if ($result === false)
{
	echo "Initialise database$separator";
	die();
}

echo "Total records: $result->num_rows$separator";

while($row = $result->fetch_assoc())
{
	$time_pre = microtime(true);
	
	$tramNumber = $row['id'];
	
	if ($readMode)
	{
		$lastupdated = new DateTime($row['lastupdated']);
		$lastupdated->setTimezone($melbourneTimezone);
		$lastupdated = $lastupdated->format('Y-m-d H:i:s');
		
		echo "Tram $tramNumber was last updated $lastupdated...<br>";
		continue;
	}
	
	// skip trams that don't have a class, we no longer care
	if (strlen(getTramClass($tramNumber)) === 0)
	{
		echo "Skipped non-existent tram $tramNumber$separator";
		$updateSkippedSql = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = 0, `lng` = 0, `routeNo` = null, `destination` = '', `lastupdated` = NOW() WHERE id = " . $tramNumber;
		$mysqli->query($updateSkippedSql);
		continue;
	}

	$serviceData = new ServiceRouteData($tramNumber, getTramClass($tramNumber), true);

	$currentLat = $serviceData->currentLat;
	$currentLon = $serviceData->currentLon;
	$routeNo = (int)$serviceData->routeNo;
	$offUsualRoute = $serviceData->offUsualRoute;
	$destination = $serviceData->destination;
	$direction = $serviceData->direction;

	if(isset($serviceData->error) && $serviceData->error == 'apierror')
	{
		$type = "API ERROR";
	}
	else if ($currentLat != "" && $currentLon != "")
	{
		if ($offUsualRoute != '1')
		{
			$offUsualRoute = 0;
		}
		
		$currentDay = new DateTime(date("Y-m-d H:i:s"));
		$currentDay->setTimezone($melbourneTimezone);
		$currentDate = new DateTime($currentDay->format('Y-m-d'));
		$unixTimestamp = $currentDate->format('U');
		$dateISO = $currentDate->format('Ymd');
		
		$updateTramLocationSql = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = " . $currentLat . ", `lng` = " . $currentLon . ", `lastupdated` = NOW(), `lastservice` = NOW(), `routeNo` = " . $routeNo . ", `offUsualRoute` = " . $offUsualRoute . ", `destination` = '" . $destination . "', `direction` = '" . $direction . "' WHERE id = " . $tramNumber;
		$mysqli->query($updateTramLocationSql);
		$insertTramHistorySql = "INSERT INTO `" . $config['dbName'] . "`.`trams_history` (`tramid`, `lat`, `lng`, `sighting` , `sighting_day` , `routeNo`, `offUsualRoute`, `destination`, `direction`) VALUES (" . $tramNumber . ", " . $currentLat . ", " . $currentLon . ", NOW(), " . $unixTimestamp . ", '" . $routeNo . "', " . $offUsualRoute . ", '" . $destination . "', '" . $direction . "')";
		$mysqli->query($insertTramHistorySql);
		$insertTramHistoryDaySql = "INSERT IGNORE INTO `" . $config['dbName'] . "`.`trams_history_for_day` (`tramid`, `routeNo`, `sighting_day`) VALUES (" . $tramNumber . ", " . $routeNo . ", " . $dateISO . ")";
		$mysqli->query($insertTramHistoryDaySql);
		$type = "LOCATION";
	}
	else
	{
		$updateDateSql = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = 0, `lng` = 0, `routeNo` = null, `destination` = '', `lastupdated` = NOW() WHERE id = " . $tramNumber;
		$mysqli->query($updateDateSql);
		$type = "DATE";
	}
	
	$time_post = microtime(true);
	$exec_time = $time_post - $time_pre;
	echo "Updated $tramNumber $type in $exec_time$separator";
}

$timeEnd = microtime(true);
$exec_time = $timeEnd - $timeStart;
echo "DONE in $exec_time$separator";
?>