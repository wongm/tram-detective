<?php

require_once('includes/functions.php');

$pageTitle = "Data quality";
require_once('includes/Header.php');

if (array_key_exists('refresh', $_GET))
{
	echo "<p>REFRESHING CONTENT!</p>";
	$data = getQualityData();
}
else
{
	$data['minsighting'] = "24/01/2018 07:20:13";
	$data['missingdata'] = array("12/06/2018", "15/06/2018", "18/07/2018", "10/02/2020", "23/09/2020-26/09/2020", "12/10/2020-13/10/2020", "20/02/2021", "26/05/2021", "3/12/2021-4/12/2021", "23/07/2023","26/02/2024-now");
}

?>
<p>The current location of each is refreshed from TramTracker every 10 minutes.</p>

<p>The oldest data logged by Tram Detective is <?php echo $data['minsighting']; ?></p>

<p><?php echo getLastUpdatedString(); ?></p>

<p>Data is missing for the following dates:</p>

<ul>
<?php 
foreach ($data['missingdata'] as $missingdata)
{
?>
	<li><?php echo $missingdata; ?></li>
<?php
}
?>
</ul>

<a href="../">Home</a>
<?
require_once('includes/Footer.php');
?>