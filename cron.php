<?php
require_once('includes/config.php');

$mysqli = new mysqli($config['dbServer'], $config['dbUsername'], $config['dbPassword'], $config['dbName']);

?>