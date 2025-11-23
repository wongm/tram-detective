<?php

ini_set('display_errors', 1);

require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/functions.php');

$historyType = "History summary";
$tramNumber = (int) $_GET['id'];

$extended = false;
$quantity = 1;
if (array_key_exists('extended', $_GET))
{
	$quantity = (int) $_GET['extended'];
	if ($quantity == 0) {
		$quantity = 1;
	}
	$historyType = "Recent history x " . $quantity;
	$extended = true;
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

$history = getAllTramHistory($tramNumber, $extended, $quantity);

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
