<?php
$pageTitle = "Tram Detective";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
?>
<h2>Find a tram</h2>
<form action="tram.php" method="get">
<label for="id">Tram number</label>
<input type="number" id="id" name="id" maxlength="4" size="4" />
<input type="submit" value="Find it!" /><input type="submit" formaction="history.php" value="View history" /></br>
</form>

<h2>Track the fleet</h2>
<p><a href="map.php">View location of all operational trams</a></p>
<p><a href="map.php?type=offroute">View location of trams off their usual route</a></p>

<h2>Tables</h2>
<p><a href="table.php?type=all">All trams</a></p>
<p><a href="table.php?type=active">Trams in service</a></p>
<p><a href="table.php?type=offroute">Trams off their usual route</a></p>
<p><a href="table.php?type=stabled">Trams stabled in depots</a></p>

<h2>Fleet listings</h2>
<p><a href="fleet.php">Melbourne's tram fleet</a></p>
<p><a href="routes.php">Tram fleet to route allocations</a></p>
<p><a href="depots.php">Tram route to depot allocations</a></p>

<h2>Social media</h2>
<p>Get alerted when trams are off their usual route!</p>
<p><a href="https://twitter.com/tramdetective">Twitter</a></p>
<p><a href="https://www.facebook.com/Tram-Detective-717904228558350/">Facebook</a></p>
<p><a href="feed.php">RSS feed</a></p>
<?php
require_once('includes/Footer.php');
?>