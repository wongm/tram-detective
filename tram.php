<?php

require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/functions.php');
require_once('includes/ServiceData.php');

$tramNumber = (int) $_GET['id'];
if (!is_numeric($tramNumber))
{
	drawErrorPage($tramNumber);
	die();
}

if (getTramClassAndNumber($tramNumber) == null)
{
	drawErrorPage($tramNumber);
	die();
}

$serviceData = new ServiceData($tramNumber, getTramClass($tramNumber), $fatMode=true);

$pageTitle = "Tram " . getTramClassAndNumber($tramNumber);
$pageDescription = "Tracking tram " . getTramClassAndNumber($tramNumber) . " around Melbourne";
require_once('includes/Header.php');

if(isset($serviceData->error))
{
	$errorclass = "alert alert-warning";

	switch ($serviceData->error)
	{
		case 'invalidroute':
			$errormessage = 'Tram is not travelling on a valid route.';
			break;
		case 'notpublic':
			$errormessage = 'Tram is not travelling on a public route.';
			break;
		case 'offnetwork':
			$errormessage = 'Tram is not on the network.';
			break;
		case 'nodata':
			$errormessage = 'Tram is on route ' . $serviceData->routeNo . ' towards ' . $serviceData->destination . ' but has no location data available.';
			break;
		case 'atterminus':
			$errormessage = 'Tram is at the route ' . $serviceData->routeNo . ' terminus at ' . $serviceData->destination . '.';
			$errorclass = "terminus";
			break;
		default:
			$errormessage = 'Unable to load data from TramTracker feed.';
			break;
	}
?>
<div class="<?php echo $errorclass ?>"><p><?php echo $errormessage ?></p></div>
<p>Last seen on the network at <?php echo getLastServiceDate($tramNumber); ?></p>
<?php

drawViewHistoryLink($tramNumber);

}
else
{
?>
<div class="inservice"><p class="alert alert-success">Currently running on route <?php echo $serviceData->routeNo ?> towards <?php echo $serviceData->destination ?>.</p>
<?php

	if ($serviceData->offUsualRoute)
	{
?>
<div><p>Tram is off the usual route for a <?php echo getTramClass($tramNumber); ?> class.</p></div>
<?php
	}

	drawViewHistoryLink($tramNumber);

    foreach ($serviceData->nextStops as $nextStop)
    {
		date_default_timezone_set("Australia/Melbourne");
		
    	// Convert to minutes, and round down
    	$predicted = strtotime($nextStop->PredictedArrivalDateTime);
    	$minutesuntil = floor(($predicted - $serviceData->currentTimestamp) / 60);

    	$minutesuntil = ($minutesuntil < 0) ? 0 : $minutesuntil;

    	// load stop data from the cache
    	$stopData = $serviceData->routeData->stops[(string)$nextStop->StopNo];

?>
<p>Stop <?php echo $stopData->Name ?>, <?php echo $stopData->SuburbName ?>: <?php echo date('g:ia', $predicted) ?> (<?php echo $minutesuntil ?> minutes)</p>
<?php
    }
}
?>
<a href="../">Home</a>
<!-- Raw data: <?php print_r($serviceData) ?>-->
<?php
require_once('includes/Footer.php');

function drawErrorPage($tramNumber)
{
	$pageTitle = "Tram " . $tramNumber;
	require_once('includes/Header.php');
?>
<div class="alert alert-danger" role="alert">
  Not a tram!
</div>
<a href="../">Home</a>
<?php
	require_once('includes/Footer.php');
}
?>
