<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__.'/includes/melb-tram-fleet/functions.php');
require_once(__DIR__.'/includes/melb-tram-fleet/routes.php');
require_once(__DIR__.'/includes/config.php');
require_once(__DIR__.'/includes/functions.php');
require_once(__DIR__.'/includes/ServiceRouteData.php');

define('UPDATE_MINUTES', 10);

if (!isset($_GET['token']) || $_GET['token'] != $config['cron'])
{
	die();
}

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);


if (isset($_GET['id']) && is_numeric($_GET['id']))
{
	runLongRemoteApiQuery($_GET['id'], $mysqli, $config, $melbourneTimezone);
}
else
{
	runShortLocalDbQuery($mysqli, $config);
}

function runShortLocalDbQuery($mysqli, $config)
{
	$readMode = isset($_GET['read']);
	
	$pages = 3;
	$pageSize = 80;
	$maxRecords = ($pages)* $pageSize;
	
	$limit = "";
	if (!$readMode)
	{
		$limit = "LIMIT 0, $maxRecords";
	}
	
	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lastupdated < (NOW() - INTERVAL " . UPDATE_MINUTES . " MINUTE) ORDER BY lastupdated ASC $limit";
	$result = $mysqli->query($tableCheck);

	if ($result === false)
	{
		echo "Initialise database";
		die();
	}
	
	// if only a few documents - shrink the page size
	if ($result->num_rows < $pageSize + ($pageSize / 8))
	{
		$pageSize = $pageSize / 2;
		$maxRecords = ($pages)* $pageSize;
	}
	
	// lots of hits on the page - offset into it, and take a page worth of data
	$randomOffset = rand(0, $pages) * ($pageSize / 4);
	$topOfRandomOffset = $randomOffset + $pageSize;
	$reset = "";
	if ($topOfRandomOffset > $result->num_rows + ($pageSize / 2))
	{
		$reset = " (was $randomOffset-$topOfRandomOffset but reset)";
		$randomOffset = 0;
		$topOfRandomOffset = $randomOffset + ($pageSize);
	}
	$skippingThreshold = ($maxRecords / (2 * $pages));
	
	echo "Updating every " . UPDATE_MINUTES . " minutes<br>";
	echo "Total records: $result->num_rows<br>";
	echo "Max records: $maxRecords<br>";
	echo "Page size: $pageSize<br>";
	echo "Skipping records if more than $skippingThreshold<br>";
	echo "Processing records $randomOffset-$topOfRandomOffset$reset<br>";

	$rowNumber = 0;
	while($row = $result->fetch_assoc())
	{
		$tramNumber = $row['id'];
		$lastupdated = $row['lastupdated'];
		
		$rowNumber++;
		
		// trying to avoid multiple attempts to update same tram record, because of concurrent DB requests
		// if we have a lot of items - skip a batch of them, based on random offset
		if (!$readMode && $result->num_rows > $skippingThreshold && ($rowNumber < $randomOffset || $rowNumber > $topOfRandomOffset))
		{
			echo "Skipped $tramNumber to try later<br>";
			continue;
		}
		
		// skip trams that don't have a class, we no longer care
		if (strlen(getTramClass($tramNumber)) === 0)
		{
			echo "Skipped non-existent tram $tramNumber<br>";
			$updateSkippedSql = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = 0, `lng` = 0, `routeNo` = null, `destination` = '', `lastupdated` = NOW() WHERE id = " . $tramNumber;
			$mysqli->query($updateSkippedSql);
			continue;
		}
		
		$url = "https://tramdetective.wongm.com/cron.php?token=" . $_GET['token'] . "&id=" . $tramNumber;
		
		echo "Update <a href=\"$url\">$tramNumber</a>... ";
		if ($readMode)
		{
			echo "Just checking - last updated $lastupdated...<br>";
			continue;
		}
		
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_NOSIGNAL => 1, //to timeout immediately if the value is < 1000 ms
			CURLOPT_TIMEOUT_MS => 500, //The maximum number of mseconds to allow cURL functions to execute
			CURLOPT_VERBOSE => 1,
			CURLOPT_HEADER => 1
		));
		$out = curl_exec($ch);
		echo "<br>";
		
		curl_close($ch);
	}
}

function runLongRemoteApiQuery($tramNumber, $mysqli, $config, $melbourneTimezone)
{
	while(ob_get_level()) ob_end_clean();
	header('Connection: close');
	ignore_user_abort();
	ob_start();
	echo('Connection Closed<BR>');
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();
	
	$secondsDelay = rand(1, 5);
	echo("Wait $secondsDelay seconds...<BR>");
	sleep($secondsDelay);
	
	// ensure no other process has updated this before us
	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lastupdated < (NOW() - INTERVAL " . UPDATE_MINUTES . " MINUTE) AND id = " . $tramNumber;
	$result = $mysqli->query($tableCheck);
	if ($result->num_rows == 0)
	{
		echo "Skipped $tramNumber ALREADY DONE<BR>";
		return;
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
	echo "Updated $tramNumber $type<BR>";
}
?>
DONE