<?php
$pageTitle = "Gunzel Operations Center";
$pageDescription = "Tracking the trams of Melbourne";
require_once('includes/Header.php');
?>
<form action="tram/" method="get">
<label for="id">Tram number</label>
<input type="number" id="id" name="id" maxlength="4" size="4" />
<input type="submit" value="Find it!" /></br>
</form>
<?php
require_once('includes/Footer.php');
?>