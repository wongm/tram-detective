<?php

require_once('includes/config.php');
require_once('includes/melb-tram-fleet/functions.php');

$mysqliConnection = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);
$melbourneTimezone = new DateTimeZone('Australia/Melbourne');

function getQualityData()
{
	global $config, $mysqliConnection, $melbourneTimezone;
	
	$tableCheck = "SELECT min(sighting) AS `minsighting` FROM `" . $config['dbName'] . "`.`trams_history`";
	$result = $mysqliConnection->query($tableCheck);
	$row = $result->fetch_assoc();

	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['minsighting']));
	$formattedprediction->setTimezone($melbourneTimezone);
	$minsighting = $formattedprediction->format('d/m/Y H:i:s');

	$data['minsighting'] = $minsighting;
	
	$tableCheck = "SELECT sighting_day FROM `" . $config['dbName'] . "`.`trams_history_for_day` GROUP BY `sighting_day`";
	$result = $mysqliConnection->query($tableCheck);
	
	$lastDate = $minsighting = $formattedprediction->format('Ymd');
	while($row = $result->fetch_assoc())
	{
		$day = $row['sighting_day'];
		
		if ($lastDate != $day)
		{
			$gapDate = new DateTime();
			$gapDate->setTimestamp(strtotime($day));
			$data['missingdata'][$day] = $gapDate->format('d/m/Y');
		}
		
		$tomorrow = new DateTime();
		$tomorrow->setTimestamp(strtotime($day));
		$tomorrow->add(new DateInterval('P1D'));
		$lastDate = $tomorrow->format('Ymd');
	}
	
	return $data;
}

function getLastUpdatedData()
{
	global $config, $mysqliConnection, $melbourneTimezone;

	$tableCheck = "SELECT max(lastupdated) AS `maxlastupdated`, min(lastupdated) AS `minlastupdated` FROM `" . $config['dbName'] . "`.`trams`";
	$result = $mysqliConnection->query($tableCheck);
	$row = $result->fetch_assoc();

	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['maxlastupdated']));
	$formattedprediction->setTimezone($melbourneTimezone);
	$maxlastupdated = $formattedprediction->format('d/m/Y H:i:s');

	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['minlastupdated']));
	$formattedprediction->setTimezone($melbourneTimezone);
	$minlastupdated = $formattedprediction->format('d/m/Y H:i:s');

	$data['maxlastupdated'] = $maxlastupdated;
	$data['minlastupdated'] = $minlastupdated;
	return $data;
}

function getLastUpdatedString()
{
	$data = getLastUpdatedData();
	return "Current data between " . $data['minlastupdated'] . " and " . $data['maxlastupdated'];
}

function getAllTrams()
{
	return getAllTramsInternal('all');
}

function getAllActiveTrams()
{
	return getAllTramsInternal('active');
}

function getAllOffRouteTrams()
{
	return getAllTramsInternal('offroute');
}

function getAllStabledTrams()
{
	return getAllTramsInternal('stabled');
}

function getAllTramsInternal($type)
{
	global $config, $mysqliConnection, $melbourneTimezone;

	switch ($type)
	{
		case 'offroute':
			$sqlWhere = 'lat != 0 AND lng != 0 AND offUsualRoute = 1';
			break;
		case 'stabled':
			$sqlWhere = 'lat = 0. AND lng = 0';
			break;
		case 'active':
			$sqlWhere = 'lat != 0 AND lng != 0';
			break;
		case 'all':
			$sqlWhere = '1=1';
			break;
	}

	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE " . $sqlWhere;
	$result = $mysqliConnection->query($tableCheck);

	if ($result === false)
	{
		return null;
	}

	$trams = [];
	while($row = $result->fetch_assoc())
	{
		$lastupdateddate = new DateTime();
		$lastupdateddate->setTimestamp(strtotime($row['lastupdated']));
		$lastupdateddate->setTimezone($melbourneTimezone);
		$formattedlastupdated = $lastupdateddate->format('d/m/Y H:i');
		
		$formattedlastservice = "";
		$order = $row['lastupdated'];
		if (substr($row['lastservice'], 0, 4) != '0000')
		{
			$lastservicedate = new DateTime();
			$lastservicedate->setTimestamp(strtotime($row['lastservice']));
			$lastservicedate->setTimezone($melbourneTimezone);
			$formattedlastservice = $lastservicedate->format('d/m/Y H:i');
			$order = $row['lastservice'];
		}

		$tram = new stdClass;
		$tram->class = getTramClass($row['id']);
		$tram->id = $row['id'];
		$tram->routeNo = $row['routeNo'];
		$tram->destination = $row['destination'];
		$tram->lastupdated = $formattedlastupdated;
		$tram->lastservice = $formattedlastservice;
		$tram->lastservicedate = $lastservicedate;
		$tram->order = $order;
		$trams[] = $tram;
	}
	
	return $trams;
}

function getLastServiceDate($id)
{
	global $config, $mysqliConnection, $melbourneTimezone;
	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams` WHERE `id` = " . $id;
	$result = $mysqliConnection->query($tableCheck);
	
	while($row = $result->fetch_assoc())
	{
		if (substr($row['lastservice'], 0, 4) != '0000')
		{
			$lastservicedate = new DateTime();
			$lastservicedate->setTimestamp(strtotime($row['lastservice']));
			$lastservicedate->setTimezone($melbourneTimezone);
			return $lastservicedate->format('d/m/Y H:i');
		}
	}
}

function getAllRouteHistory($route)
{
    global $config, $mysqliConnection, $melbourneTimezone;
	
	$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams_history_for_day` WHERE `routeNo` = " . $route . " ORDER BY `sighting_day` DESC, tramid ASC";
	$result = $mysqliConnection->query($tableCheck);
	
	if ($result === false)
	{
		return null;
	}

	$history = array();
	while($row = $result->fetch_assoc())
	{
		$day = $row['sighting_day'];
		
		if (!isset($history[$day]))
		{
			$history[$day] = new stdClass;
			$history[$day]->trams = array();
			$history[$day]->airconCount = 0;
			$history[$day]->lowFloorCount = 0;
			$history[$day]->totalCount = 0;
		}
		
		$sighting = new DateTime();
		$sighting->setTimestamp(strtotime($day));
		$formattedDate = $sighting->format('d/m/Y');
		
		$tramId = $row['tramid'];
		array_push($history[$day]->trams, $tramId);
		$history[$day]->date = $formattedDate;
		$history[$day]->timestamp = $sighting->getTimestamp() * 1000;
		$history[$day]->order = $day;
		$history[$day]->totalCount++;
		
		if (getTramAirConditioned($tramId))
		{
    		$history[$day]->airconCount++;
		}
		if (getTramLowFloor($tramId))
		{
    		$history[$day]->lowFloorCount++;
		}
		
	    $history[$day]->lowFloorPercent = round(($history[$day]->lowFloorCount / $history[$day]->totalCount) * 100, 0);
	    $history[$day]->airconPercent = round(($history[$day]->airconCount / $history[$day]->totalCount) * 100, 0);
	    
	    $history[$day]->nonLowFloorCount = $history[$day]->totalCount - $history[$day]->lowFloorCount;
	    $history[$day]->nonAirconCount = $history[$day]->totalCount - $history[$day]->airconCount;
	}
	
	return $history;
}

function getAllTramHistory($id, $extended, $complete)
{
	global $config, $mysqliConnection, $melbourneTimezone;
	
	if ($extended || $complete)
	{
		$limit = "";
		if ($extended)
		{
			$limit = " LIMIT 100";
		}
		
		$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams_history` WHERE `tramid` = " . $id . " ORDER BY `id` DESC" . $limit;
	}
	else
	{
		$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams_history_for_day` WHERE `tramid` = " . $id . " ORDER BY `sighting_day` DESC, routeNo ASC";
	}
	$result = $mysqliConnection->query($tableCheck);
	
	if ($result === false)
	{
		return null;
	}

	$history = array();
	while($row = $result->fetch_assoc())
	{
		if ($extended || $complete)
		{
			$day = $row['sighting'];
			$sighting = new DateTime();
			$sighting->setTimestamp(strtotime($day));
			$sighting->setTimezone($melbourneTimezone);
			
			$history[$day] = new stdClass;
			$history[$day]->routes = array();
			array_push($history[$day]->routes, $row['routeNo']);
			$history[$day]->date = $sighting->format('d/m/Y H:i');
			$history[$day]->order = $day;
		}
		else
		{
			$day = $row['sighting_day'];
			
			if (!isset($history[$day]))
			{
				$history[$day] = new stdClass;
				$history[$day]->routes = array();
			}
			
			$sighting = new DateTime();
			$sighting->setTimestamp(strtotime($day));
			$formattedDate = $sighting->format('d/m/Y');
			
			array_push($history[$day]->routes, $row['routeNo']);
			$history[$day]->date = $formattedDate;
			$history[$day]->order = $day;
		}
		}
	
	return $history;
}

function drawTable($trams, $type)
{
	if ($trams == null)
	{
		echo '<div class="alert alert-warning">No trams found!</div>';
	}
	else
	{
		echo "<table class=\"sortable-theme-bootstrap\" data-sortable><thead>";
		
		$headers = "<tr><th>Tram</th><th>#</th>";
		if ($type != 'stabled')
		{
			$headers .= "<th>Route</th><th>Towards</th>";
		}
		
		if ($type == 'stabled' || $type == 'all')
		{
			$headers .= "<th>Last seen</th>";
		}
		else
		{
			$headers .= "<th>Last updated</th>";
		}
		
		echo "$headers<th data-sortable=\"false\"></th></tr></thead><tbody>";

		foreach($trams as $tram){
			$field = get_object_vars($tram); 
			
			// ignore trams no longer mapped to a class
			if ($tram->class == '')
				continue;
			
			echo "<tr>\r\n";
			echo "<td>$tram->class</td>\r\n";
			echo "<td>$tram->id</td>\r\n";

			if ($type != 'stabled')
			{
				echo "<td>$tram->routeNo</td>\r\n";
				echo "<td>$tram->destination</td>\r\n";
			}

			if ($tram->routeNo == '' || $type == 'stabled')
			{
				if ($tram->lastservice == '')
				{
					$tram->lastservice = 'Never';
					$tram->order = 0;
				}
				
				echo "<td data-value=\"" . $tram->order . "\">$tram->lastservice</td>\r\n";
				echo "<td><a href=\"history.php?id=" . $tram->id . "\">History</a></td>\r\n";
			}
			else
			{
				if ($type == 'all')
				{
					$tram->lastupdated = "In service";
				}
				
				echo "<td data-value=\"" . $tram->order . "\">$tram->lastupdated</td>\r\n";
				echo "<td><a href=\"tram.php?id=" . $tram->id . "\">Current location</a></td>\r\n";
			}
			echo "</tr>\r\n";
		}
		echo "</tbody></table>";
	}

	echo "<p>" . getLastUpdatedString() . "</p>";
}

function exportTramHistory()
{
	global $config, $mysqliConnection;
	
	$tableCheck = "SELECT sighting_day, tramid, routeNo FROM `" . $config['dbName'] . "`.`trams_history_for_day` ORDER BY `sighting_day` DESC, tramid ASC, routeNo ASC";
	$result = $mysqliConnection->query($tableCheck);
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function array2csv(array &$array)
{
	if (count($array) == 0)
	{
		return null;
	}
	ob_start();
	$df = fopen("php://output", 'w');
	fputcsv($df, array_keys(reset($array)));
	foreach ($array as $row) 
	{
		fputcsv($df, $row);
	}
	fclose($df);
	return ob_get_clean();
}

function download_send_headers($filename)
{
	// disable caching
	$now = gmdate("D, d M Y H:i:s");
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
	header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	header("Last-Modified: {$now} GMT");

	// force download  
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");

	// disposition / encoding on response body
	header("Content-Disposition: attachment;filename={$filename}");
	header("Content-Transfer-Encoding: binary");
}

function drawViewHistoryLink($tramNumber)
{
	echo "<p><a href=\"tram.php?id=" . $tramNumber . "\">View current location</a> - ";
	echo "<a href=\"history.php?id=" . $tramNumber . "\">Service history</a> - ";
	echo "<a href=\"history.php?id=" . $tramNumber . "&extended=\">Recent movements</a> - ";
	echo "<a href=\"history.php?id=" . $tramNumber . "&complete=\">Complete history</a></p>";
}

?>