<?php

require_once('includes/config.php');
require_once('includes/melb-tram-fleet/functions.php');

$mysqliConnection = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);
$melbourneTimezone = new DateTimeZone('Australia/Melbourne');

function getLastUpdatedString()
{
	global $config, $mysqliConnection, $melbourneTimezone;

	$tableCheck = "SELECT max(lastupdated) AS `maxlastupdated`, min(lastupdated) AS `minlastupdated` FROM `" . $config['dbName'] . "`.`trams` WHERE lat != 0 AND lng != 0";
	$result = $mysqliConnection->query($tableCheck);
	$row = $result->fetch_assoc();

	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['maxlastupdated']));
	$formattedprediction->setTimezone($melbourneTimezone);	
	$maxlastupdated = $formattedprediction->format('d/m/Y H:i');

	$formattedprediction = new DateTime();
	$formattedprediction->setTimestamp(strtotime($row['minlastupdated']));
	$formattedprediction->setTimezone($melbourneTimezone);
	$minlastupdated = $formattedprediction->format('d/m/Y H:i');
	
	return "Data between $minlastupdated and $maxlastupdated";
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
		if (substr($row['lastservice'], 0, 4) != '0000')
		{
			$lastservicedate = new DateTime();
			$lastservicedate->setTimestamp(strtotime($row['lastservice']));
			$lastservicedate->setTimezone($melbourneTimezone);
			$formattedlastservice = $lastservicedate->format('d/m/Y H:i');
		}

		$tram = new stdClass;
		$tram->class = getTramClass($row['id']);
		$tram->id = $row['id'];
		$tram->routeNo = $row['routeNo'];
		$tram->destination = $row['destination'];
		$tram->lastupdated = $formattedlastupdated;
		$tram->lastservice = $formattedlastservice;
		$trams[] = $tram;
	}
	
	return $trams;
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
			$headers .= "<th>Route</th><th>Towards</th><th>Last updated</th><th data-sortable=\"false\"></th>";
		}
		else
		{
			$headers .= "<th>Last seen</th>";
		}
		
		echo "$headers</tr></thead><tbody>";

		foreach($trams as $tram){
			$field = get_object_vars($tram); 
			echo "<tr>";
			foreach($field as $key => $value )
			{
				if (($type != 'stabled' && $key != "lastservice") || $key == "class" || $key == "id")
				{
					echo "<td>$value</td>";
				}
			}
			
			if ($type == 'stabled')
			{
				echo "<td>$tram->lastservice</td>";
			}
			else
			{
				echo "<td><a href=\"tram.php?id=" . $tram->id . "\">View current location</a></td>";
			}
			echo "</tr>";
		}
		echo "</tbody></table>";
	}

	echo "<p>" . getLastUpdatedString() . "</p>";
}

?>