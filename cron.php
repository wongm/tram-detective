<?php
require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/melb-tram-fleet/routes.php');
require_once('includes/config.php');
require_once('includes/functions.php');
require_once('includes/ServiceRouteData.php');

if (!isset($_GET['token']) || $_GET['token'] != $config['cron'])
{
	die();
}

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

error_reporting(E_ALL); 
ini_set('display_errors', 1);

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
	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE lastupdated < (NOW() - INTERVAL 10 MINUTE) ORDER BY lastupdated ASC LIMIT 0, 40";
	$result = $mysqli->query($tableCheck);

	if ($result === false)
	{
		echo "Initialise database";
		die();
	}

	while($row = $result->fetch_assoc())
	{
		$tramNumber = $row['id'];
		
		// skip trams that don't have a class, we no longer care
		if (strlen(getTramClass($tramNumber)) === 0)
		{
			echo "Skipped $tramNumber<BR>";
			$updateSkippedSql = "UPDATE `" . $config['dbName'] . "`.`trams` SET `lat` = 0, `lng` = 0, `routeNo` = null, `destination` = '', `lastupdated` = NOW() WHERE id = " . $tramNumber;
			$mysqli->query($updateSkippedSql);
			continue;
		}
		
		$url = "https://tramdetective.wongm.com/cron.php?token=" . $_GET['token'] . "&id=" . $tramNumber;
		
		
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_NOSIGNAL => 1, //to timeout immediately if the value is < 1000 ms
			CURLOPT_TIMEOUT_MS => 50, //The maximum number of mseconds to allow cURL functions to execute
			CURLOPT_VERBOSE => 1,
			CURLOPT_HEADER => 1
		));
		$out = curl_exec($ch);
		
		echo "Hit $url<BR>";
		curl_close($ch);
	}
}

function runLongRemoteApiQuery($tramNumber, $mysqli, $config, $melbourneTimezone)
{
	while(ob_get_level()) ob_end_clean();
	header('Connection: close');
	ignore_user_abort();
	ob_start();
	echo('Connection Closed');
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush();
	flush();

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
	echo "<BR>Updated $tramNumber $type<BR>";
}
?>
DONE