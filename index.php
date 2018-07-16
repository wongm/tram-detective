<?php
$pageTitle = "Gunzel Operations Center";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
?>
<h2>Find a tram</h2>
<form action="tram.php" method="get">
<label for="id">Tram number</label>
<input type="number" id="id" name="id" maxlength="4" size="4" />
<input type="submit" value="Find it!" /></br>
</form>

<h2>Track the fleet</h2>
<p><a href="map.php">View location of all operational trams</a></p>
<p><a href="map.php?type=offroute">View location of trams off their usual route</a></p>
<p><a href="table.php?type=active">List of all trams in service</a></p>
<p><a href="table.php?type=offroute">List of all trams off their usual route</a></p>
<p><a href="table.php?type=stabled">List of all trams stabled in depots</a></p>
<h2>Tables</h2>
<p><a href="table.php?type=all">All trams</a></p>
<p><a href="fleet.php">Melbourne's tram fleet</a></p>
<p><a href="routes.php">Tram fleet to route allocations</a></p>
<?php
require_once('includes/Footer.php');
?>