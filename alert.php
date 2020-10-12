<?php
header("Content-Type: application/rss+xml; charset=ISO-8859-1");

$server = 'https://' . $_SERVER['SERVER_NAME'];

$rssfeed .= "<rss version=\"2.0\">\r\n";
$rssfeed .= "<channel>\r\n";
$rssfeed .= "<title>Data feed alerts</title>\r\n";
$rssfeed .= "<link>$server</link>\r\n";
$rssfeed .= "<description>Is there an issue with stale data?</description>\r\n";
$rssfeed .= "<language>en-au</language>\r\n";
$rssfeed .= "<copyright>Copyright (C) " . date("Y") . " Marcus Wong</copyright>\r\n";

echo $rssfeed;
require_once('includes/functions.php');

$data = getLastUpdatedData();

if ($data['alert'])
{
	$date = $data['maxlastupdatedtimestamp']->format(DateTime::RFC822);
    $link = "$server/quality.php?date=" . urlencode($date);
?>
    <item>
    <title>Tram Detective data feed out of date!</title>
    <link><![CDATA[<?php echo $link; ?>]]></link>
    <description>Tram Detective data feed last updated at <?php echo $date; ?></description>
    <guid><?php echo $date; ?></guid>
    <pubDate><?php echo $date; ?></pubDate>
    </item>
<?php
}
?>
</channel>
</rss>
