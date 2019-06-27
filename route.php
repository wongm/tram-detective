<?php

ini_set('display_errors', 1);

require_once('includes/melb-tram-fleet/functions.php');
require_once('includes/functions.php');

$route = (int) $_GET['id'];
if (!is_numeric($route))
{
	drawErrorPage();
	die();
}
$mode = 'graph';
$tableMode = false;
if (isset($_GET['mode']) && $_GET['mode'] == 'fleet')
{
    $mode = 'fleet';
    $tableHeader = "<table class=\"sortable-theme-bootstrap\" data-sortable><thead><tr><th>Date</th><th>Trams</th></tr></thead><tbody>";
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'table')
{
    $mode = 'table';
    $tableHeader = "<table class=\"sortable-theme-bootstrap\" data-sortable><thead><tr><th>Date</th><th>Low floor %</th><th>Air conditioned %</th></tr></thead><tbody>";
}

$pageTitle = "Trams on route " . $route;
$pageDescription = "Tracking trams on route " . $route . " around Melbourne";
require_once('includes/Header.php');

$history = getAllRouteHistory($route);

?>
<p><a href="?id=<?php echo $route; ?>">Graph</a> | <a href="?id=<?php echo $route; ?>&mode=table">Table</a> | <a href="?id=<?php echo $route; ?>&mode=fleet">Fleet number listing</a></p>
<?php

if ($mode == 'graph')
{
?>
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
    
<div id="tramGraph" style="min-width: 310px; height: 400px; margin: 0 auto"></div>

<script>
jQuery(document).ready(function() {
    jQuery('#tramGraph').highcharts({
        chart: {
            type: 'line'
        },
        title: {
            text: ''
        },
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats:{
                year: '%Y'
            },
            tickmarkPlacement: 'on',
            title: {
                enabled: false
            }
        },
        yAxis: {
            max: 100,
            title: {
                text: '# of trams'
            },
            min: 0
        },
        tooltip: {
            shared: true,
            formatter: function() {
                var s = '<b>'+ Highcharts.dateFormat('%d %B %Y', this.x) +'</b>';
                var sum = 0;
                jQuery.each(this.points, function(i, point) {
                    s += '<br/><span style=\"color:' + point.series.color + '\">' + point.series.name + 
                            '</span>: <b>' + point.y + '%</b>';
                    sum += point.y;
                });
                return s;
            },
        },
        plotOptions: {
            area: {
                stacking: 'normal',
                lineColor: '#666666',
                lineWidth: 1,
                marker: {
                    enabled: false,
                    symbol: 'circle',
                    radius: 2,
                    states: {
                        hover: {
                            enabled: true
                        }
                    }
                }
            }
        },
        series: [{
            name: 'Air conditioned trams',
            data: [
            <?php foreach($history as $day) { ?>
                [<?php echo $day->timestamp; ?>, <?php echo $day->airconPercent; ?> ],
            <?php } ?>
            ]
        }, {
            name: 'Low floor trams',
            data: [
            <?php foreach($history as $day) { ?>
                [<?php echo $day->timestamp; ?>, <?php echo $day->lowFloorPercent; ?> ],
            <?php } ?>
            ]
        }]
    });
});
</script>


<?php
}
else
{
    echo $tableHeader;
    foreach($history as $day)
    {
    	echo "<tr><td data-value=\"" . $day->order . "\">" . $day->date . "</td>";
    	
    	if ($mode == 'fleet')
    	{
        	echo "<td>" . implode(', ', $day->trams) . "</td>";
    	}
    	else if ($mode == 'table')
    	{
        	echo "<td>" . $day->lowFloorPercent . "% (" . $day->nonLowFloorCount . " high floor)</td><td>" . $day->airconPercent . "% (" . $day->nonAirconCount . " non-air conditioned)</td>";
    	}
    	echo "</tr>\r\n";
    }
    echo "</tbody></table>";
}

?>
<a href="../">Home</a>
<?php
require_once('includes/Footer.php');

function drawErrorPage($tramNumber)
{
	$pageTitle = "Route " . $tramNumber;
	require_once('includes/Header.php');
?>
<div class="alert alert-danger" role="alert">
  ROute not found!
</div>
<a href="../">Home</a>
<?php
	require_once('includes/Footer.php');
}
?>
