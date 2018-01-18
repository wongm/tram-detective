<?php

ini_set('display_errors', 1);

require_once('includes/MelbourneTrams.php');
require_once('includes/ServiceData.php');

date_default_timezone_set("Australia/Melbourne");

$tramNo = (int) $_GET['id'];
if (!is_numeric($tramNo))
{
	drawErrorPage();
	die();
}

$serviceData = new ServiceData($tramNo, $fatMode=true);

if (getTramClassAndNumber($tramNo) == null)
{
	drawErrorPage();
	die();
}

$pageTitle = "Tram " . getTramClassAndNumber($tramNo);
$pageDescription = "Tracking tram " . getTramClassAndNumber($tramNo) . " around Melbourne";
require_once('includes/Header.php');

if(isset($serviceData->error))
{
	$errorclass = "error";
			
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
		case 'atterminus':
			$errormessage = 'Tram is at the route ' . $serviceData->routeNo . ' terminus at ' . $serviceData->destination . '.';
			$errorclass = "terminus";
			break;
	}
?>
<div class="<?php echo $errorclass ?>"><p><?php echo $errormessage ?></p></div>
<?php
}
else
{
?>
<div class="inservice"><p>Currently running on route <?php echo $serviceData->routeNo ?> towards <?php echo $serviceData->destination ?>.</p>
<?php

    foreach ($serviceData->nextStops as $nextStop)
    {
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

function drawErrorPage()
{
    echo "Not a tram!";
}
?>