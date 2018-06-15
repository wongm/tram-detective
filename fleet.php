<?php
$pageTitle = "Melbourne's tram fleet";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
include_once('includes/melb-tram-fleet/functions.php');
?>
<p>This should list all trams currently in service with Yarra Trams on the Melbourne tramway network</p>
<p>Search for tram photos at <a href="http://railgallery.wongm.com/">http://railgallery.wongm.com/</a></p>
<ul>
<?php
foreach ($melbourne_trams as $class => $trams)
{
    echo "<li><p>$class class:</p><ul>";
    
    foreach ($trams as $tram_number)
    {
        $tram = $class . '.' . $tram_number;
        echo '<li><a href="https://railgallery.wongm.com/page/search/' . $tram . '">' . $tram . '</a>';
    }
    
    echo "</ul></li>";
}
?>
</ul>
<a href="/">Home</a>
<?php
require_once('includes/Footer.php');
?>
