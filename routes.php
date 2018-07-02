<?php
$pageTitle = "Melbourne tram routes";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
include_once('includes/melb-tram-fleet/routes.php');
?>
<p>This should list what Melbourne routes a given tram class operates on.</p>
<ul>
<?php
foreach ($tram_routes as $class => $routes)
{
    echo "<li><p>$class class:</p><ul>";
    
    foreach ($routes as $route)
    {
        echo "<li>Route $route</a>";
    }
    
    echo "</ul></li>";
}
?>
</ul>
<a href="/">Home</a>
<?php
require_once('includes/Footer.php');
?>
