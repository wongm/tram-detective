<?php

require_once('includes/config.php');
require_once('includes/functions.php');

set_time_limit(0);
ini_set('memory_limit', -1);

$array = exportTramHistory();
download_send_headers("tram_data_export_" . date("Y-m-d") . ".csv");
echo array2csv($array);
die();

?>