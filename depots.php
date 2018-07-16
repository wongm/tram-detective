<?php
$pageTitle = "Melbourne tram depots";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
include_once('includes/melb-tram-fleet/depots.php');
?>
<p>This should list what Melbourne depots operate a given tram route.</p>
<ul>
<?php
foreach ($tram_depots as $depot => $routes)
{
    echo "<li><p>$depot depot:</p><ul>";
    echo "<li><p>Routes " . join(', ', $routes) . "</p></li>";
    echo "</ul></li>";
}
?>
</ul>
<a href="/">Home</a>
<?php
require_once('includes/Footer.php');
?>
