<?php

require_once('includes/functions.php');

$type = $_GET['type'];
switch ($type)
{
	case 'offroute':
		$pageTitle = "Trams off their usual route";
		$trams = getAllOffRouteTrams();
		break;
	case 'stabled':
		$pageTitle = "Trams stabled in depots";
		$trams = getAllStabledTrams();
		break;
	case 'active':
		$pageTitle = "Trams in service";
		$trams = getAllActiveTrams();
		break;
	case 'all':
		$pageTitle = "All trams";
		$trams = getAllTrams();
		break;
}

$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');

echo "<p>Click column headers to reorder - " . sizeof($trams) . " trams in total</p>";

drawTable($trams, $type);
?>
<a href="/">Home</a>
<?php
require_once('includes/Footer.php');
?>
