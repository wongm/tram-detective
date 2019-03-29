<?php

require_once('includes/config.php');
require_once('includes/functions.php');

$currentDay = new DateTime(date("Y-m-d H:i:s"));
$currentDay->setTimezone($melbourneTimezone);
$timestamp = $currentDay->format('U');
$dayTimestamp = new DateTime($currentDay->format('Y-m-d'));
$dayTimestamp = $dayTimestamp->format('U');

$plainDay = new DateTime(date("Y-m-d H:i:s"));
$plainTimestamp = $plainDay->format('U');
$plainDayTimestamp = new DateTime($plainDay->format('Y-m-d'));
$plainDayTimestamp = $plainDayTimestamp->format('U');
?>
<p>Melbourne time stamp: <?php echo $timestamp ?></p>
<p>Melbourne time stamp for day: <?php echo $dayTimestamp ?></p>
<p>Melbourne time formatted: <?php echo $currentDay->format('Y-m-d H:i:s') ?></p>

<p>Server time stamp: <?php echo $plainTimestamp ?></p>
<p>Server time stamp for day: <?php echo $plainDayTimestamp ?></p>
<p>Server time formatted: <?php echo $plainDay->format('Y-m-d H:i:s') ?></p>