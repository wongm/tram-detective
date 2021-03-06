<?php

ini_set('display_errors', 1);

require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/functions.php');

$historyType = "History summary";
$tramNumber = (int) $_GET['id'];

$extended = $complete = false;
if (array_key_exists('extended', $_GET))
{
	$historyType = "Recent history";
	$extended = true;
}
if (array_key_exists('complete', $_GET))
{
	$historyType = "Complete history";
	$complete = true;
}
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

$pageTitle = "Tram " . getTramClassAndNumber($tramNumber) . " - " . $historyType;
$pageDescription = "Tracking tram " . getTramClassAndNumber($tramNumber) . " around Melbourne";
require_once('includes/Header.php');

$history = getAllTramHistory($tramNumber, $extended, $complete);

drawViewHistoryLink($tramNumber);

echo "<table class=\"sortable-theme-bootstrap\" data-sortable><thead><tr><th>Date</th><th>Routes</th></tr></thead><tbody>";
foreach($history as $day)
{
	echo "<tr><td data-value=\"" . $day->order . "\">" . $day->date . "</td><td>" . implode(', ', $day->routes) . "</td></tr>\r\n";
}
echo "</tbody></table>";

?>
<a href="../">Home</a>
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
