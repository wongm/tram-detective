<?php

require_once('includes/config.php');

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

$tableCheck = "SELECT * FROM information_schema.tables WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "CREATE TABLE `" . $config['dbName'] . "`.`trams` ( `id` INT NOT NULL , `lastupdated` DATETIME NOT NULL , `lat` DECIMAL NOT NULL , `long` DECIMAL NOT NULL )";
	$result = $mysqli->query($tableCreate);
	echo "Table created!<BR>";
}

require_once('includes/melb-tram-fleet/trams.php');

foreach (array_keys($melbourne_trams) as $class)
{
	foreach ($melbourne_trams[$class] as $tramNumber)
	{
		checkOrInsertTramNumber($mysqli, $config, $tramNumber);
	}
}

echo "Insert complete";

function checkOrInsertTramNumber($mysqli, $config, $tramNumber)
{
	$tableCheck = "SELECT id FROM `" . $config['dbName'] . "`.`trams` WHERE id = " . $tramNumber;
	$result = $mysqli->query($tableCheck);
	if ($result->num_rows == 0)
	{
		$tableCheck = "INSERT INTO `" . $config['dbName'] . "`.`trams` (id, lastupdated) VALUES (" . $tramNumber . ", NOW())";
		$result = $mysqli->query($tableCheck);
		echo "Inserted $tramNumber<BR>";
	}
}
?>