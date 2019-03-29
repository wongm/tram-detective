<?php

require_once('includes/config.php');

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

$tableCheck = "SELECT * FROM information_schema.tables WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "CREATE TABLE `" . $config['dbName'] . "`.`trams` ( `id` INT NOT NULL , PRIMARY KEY (`id`), `lastupdated` DATETIME NOT NULL , `lat` DECIMAL(10, 8) NOT NULL , `lng` DECIMAL(11, 8) NOT NULL, routeNo INT NULL, offUsualRoute BOOL, destination CHAR(50), direction CHAR(10) )";
	$result = $mysqli->query($tableCreate);
	echo "`trams` table created!<BR>";
}

$tableCheck = "SELECT * FROM information_schema.tables WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams_history' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "CREATE TABLE `" . $config['dbName'] . "`.`trams_history` ( `id` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`) ,`tramid` INT NOT NULL , `sighting` DATETIME NOT NULL , `lat` DECIMAL(10, 8) NOT NULL , `lng` DECIMAL(11, 8) NOT NULL, routeNo INT NULL, offUsualRoute BOOL, destination CHAR(50), direction CHAR(10) )";
	$result = $mysqli->query($tableCreate);
	echo "`trams_history` table created!<BR>";
}

$tableCheck = "SELECT * FROM information_schema.columns WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams' AND column_name = 'lastservice' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "ALTER TABLE `" . $config['dbName'] . "`.`trams` ADD COLUMN `lastservice` DATETIME NOT NULL AFTER `lastupdated`";
	$result = $mysqli->query($tableCreate);
	echo "`lastservice` column added to `trams` table!<BR>";
}

$tableCheck = "SELECT * FROM information_schema.columns WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams_history' AND column_name = 'sighting_day' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "ALTER TABLE `" . $config['dbName'] . "`.`trams_history` ADD COLUMN `sighting_day` INT(8) UNSIGNED AFTER `sighting`";
	$result = $mysqli->query($tableCreate);
	echo "`sighting_day` column added to `trams_history` table!<BR>";
}

$tableCheck = "SELECT * FROM `" . $config['dbName'] . "`.`trams_history` WHERE `sighting_day` IS NULL;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows > 0)
{
	$tableUpdate = "UPDATE `" . $config['dbName'] . "`.`trams_history` SET `sighting_day` = UNIX_TIMESTAMP(DATE(CONVERT_TZ(`sighting`,'-04:00','+10:00'))) WHERE `sighting_day` IS NULL";
	$result = $mysqli->query($tableUpdate);
	echo "Backfilled `sighting_day` column on `trams_history` table!<BR>";
}

$tableCheck = "SELECT * FROM information_schema.tables WHERE table_schema = '" . $config['dbName'] . "' AND table_name = 'trams_history_for_day' LIMIT 1;";
$result = $mysqli->query($tableCheck);

if ($result->num_rows == 0)
{
	$tableCreate = "CREATE TABLE `" . $config['dbName'] . "`.`trams_history_for_day` ( `id` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`), tramid INT NOT NULL, routeNo INT NOT NULL, sighting_day INT(8) NOT NULL )";
	$result = $mysqli->query($tableCreate);
	echo "`trams_history_for_day` table created!<BR>";
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