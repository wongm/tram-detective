<?php
header("Content-Type: application/rss+xml; charset=ISO-8859-1");

$server = 'https://' . $_SERVER['SERVER_NAME'];

$rssfeed = "<rss version=\"2.0\">\r\n";
$rssfeed .= "<channel>\r\n";
$rssfeed .= "<title>Trams off their usual route</title>\r\n";
$rssfeed .= "<link>$server</link>\r\n";
$rssfeed .= "<description>Melbourne trams operating away from their usual route</description>\r\n";
$rssfeed .= "<language>en-au</language>\r\n";
$rssfeed .= "<copyright>Copyright (C) " . date("Y") . " Marcus Wong</copyright>\r\n";

echo $rssfeed;
require_once('includes/functions.php');

foreach(getAllOffRouteTrams() as $tram)
{
    $sighting = $tram->lastservicedate->format('l d F G:i');
    $title = "$sighting: tram $tram->class.$tram->id currently running on route $tram->routeNo towards $tram->destination";
    $time = strtotime($tram->lastupdated);
    $date = $tram->lastservicedate->format('Y-m-d');
    $link = "$server/tram.php?id=$tram->id&date=$date";
    $guid = $tram->id . '-' . $date;
?>
    <item>
    <title><?php echo $title; ?></title>
    <link><![CDATA[<?php echo $link; ?>]]></link>
    <description><?php echo $title; ?></description>
    <guid><?php echo $guid; ?></guid>
    <pubDate><?php echo $date; ?></pubDate>
    </item>
<?php
}
?>
</channel>
</rss>
