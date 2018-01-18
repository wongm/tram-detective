<?php

DEFINE('NAMESPACE', 'http://www.yarratrams.com.au/pidsservice/');

require_once('RouteData.php');
require_once('XSoapClient.php');

class ServiceData extends Persistent
{
	var $tramNumber;
	var $routeNo;
	var $destination;
	var $direction;
	var $nextStops;
	var $currentTimestamp;
	var $currentLat;
	var $currentLon;
	var $routeData;
	var $error;

	private $soapClient;

	function __construct($tramNumber, $tramClass)
	{
		$this->soapClient = new XSoapClient("http://ws.tramtracker.com.au/pidsservice/pids.asmx?wsdl");

		// Prepare SoapHeader parameters
		$sh_param = new stdClass();
		$sh_param->ClientGuid = 'f8d92cfe-5b58-437a-af5b-c76d8e151507';
		$sh_param->ClientType = 'WEBPID';
		$sh_param->ClientVersion = '1.1.0';
		$sh_param->ClientWebServiceVersion = '6.4.0.0';
		$headers = new SoapHeader('http://www.yarratrams.com.au/pidsservice/', 'PidsClientHeader', $sh_param);

		// Prepare Soap Client
		$this->soapClient->__setSoapHeaders(array($headers));

		// Now the heavy lifting
		$this->loadServiceData($tramNumber, $tramClass);
	}

	private function loadServiceData($tramNumber, $tramClass)
	{
		// Setup the GetNextPredictedArrivalTimeAtStopsForTramNo parameters
		$ap_param = array( 'tramNo' => $tramNumber);

		// Call GetNextPredictedArrivalTimeAtStopsForTramNo ()
		$error = 0;
		try {
		    $info = $this->soapClient->GetNextPredictedArrivalTimeAtStopsForTramNo($ap_param);
		} catch (SoapFault $fault) {
		    $error = 1;
		    $this->error = "SOAP error. Code: ".$fault->faultcode.". Message: ".$fault->faultstring.".";
			return;
		}


		if (isset($info->GetNextPredictedArrivalTimeAtStopsForTramNoResult))
		{
			$serviceResults = simplexml_load_string($info->GetNextPredictedArrivalTimeAtStopsForTramNoResult->any);

			$this->tramNumber = (string) $serviceResults->NewDataSet->TramNoRunDetailsTable->tramNumber;
			$this->routeNo = (string) $serviceResults->NewDataSet->TramNoRunDetailsTable->RouteNo;
			$this->nextStops = $serviceResults->NewDataSet->NextPredictedStopsDetailsTable;

			$this->offUsualRoute = $this->checkUsualRoute($tramClass, $this->routeNo);

			$this->currentTimestamp = time();

			$isUpDirection = ((string) $serviceResults->NewDataSet->TramNoRunDetailsTable->Up) == 'true';
			$atLayover = ((string) $serviceResults->NewDataSet->TramNoRunDetailsTable->AtLayover) == 'true';
			$available = ((string) $serviceResults->NewDataSet->TramNoRunDetailsTable->Available) == 'true';
			$this->direction = $isUpDirection ? 'up' : 'down';

			if (!$available)
			{
				$this->error = "notpublic";
				return;
			}

			if ($atLayover && sizeof($this->nextStops) == 0)
			{
				$this->error = "atterminus";
				return;
			}
		}

		if (!isset($serviceResults->NewDataSet))
		{
			$this->error = "offnetwork";
			return;
		}

		$cacheLocation = "cache/route/route" . $this->routeNo . $this->direction . ".ser";

		$this->routeData = $this->loadRouteData($cacheLocation, $isUpDirection);
		$this->destination = $isUpDirection ? $this->routeData->upDirection : $this->routeData->downDirection;

		$currentStopNo = (string)$this->nextStops[0]->StopNo;
		$currentLocation = $this->routeData->stops[$currentStopNo];
		$this->currentLat = $currentLocation->Latitude;
		$this->currentLon = $currentLocation->Longitude;
	}

	private function checkUsualRoute($tramClass, $routeNo)
	{
		require('melb-tram-fleet/routes.php');
		return !in_array($routeNo, $tram_routes[$tramClass]);
	}

	private function loadRouteData($cacheLocation, $isUpDirection)
	{
		$routeData = new RouteData($cacheLocation);
		$routeData->open();

		//if no cached data exists, then load it then persist
		if(isset($routeData->routeNo))
		{
			return $routeData;
		}

		// load route destination details
		$destinationParam = array( 'routeNo' => $this->routeNo);
	    $destinationInfo = $this->soapClient->GetDestinationsForRoute($destinationParam);
		$destinationXML = ($destinationInfo->GetDestinationsForRouteResult->any);
		preg_match('/<UpDestination>(.*)<\/UpDestination>/', $destinationXML, $upMatches);
		preg_match('/<DownDestination>(.*)<\/DownDestination>/', $destinationXML, $downMatches);
		$routeData->upDirection = $upMatches[1];
		$routeData->downDirection = $downMatches[1];

		if (strlen($destinationInfo->validationResult) > 0)
		{
			$this->error = "invalidroute";
			return;
		}

		// load route stop details
		$stopParam = array( 'routeNo' => $this->routeNo, 'isUpDirection' => ($isUpDirection ? true : false) );
	    $stopInfo = $this->soapClient->GetListOfStopsByRouteNoAndDirection($stopParam);
		$stopsXML = new SimpleXMLElement("<NewDataSet>" . $stopInfo->GetListOfStopsByRouteNoAndDirectionResult->any . "</NewDataSet>");
		$stopsXML->registerXPathNamespace('diffgr', 'urn:schemas-microsoft-com:xml-diffgram-v1');
		$stopElements = $stopsXML->xpath('diffgr:diffgram/DocumentElement');

		// mash the data into an easy to load array
		$stopsResults = array();
		foreach ($stopElements[0] as $stopElement)
		{
			// map data to be serialisable
			$key = (string)$stopElement->TID;
			$stopsResultsElement = new stdClass;
			$stopsResultsElement->Name = (string)$stopElement->Name;
			$stopsResultsElement->Description = (string)$stopElement->Description;
			$stopsResultsElement->Latitude = (string)$stopElement->Latitude;
			$stopsResultsElement->Longitude = (string)$stopElement->Longitude;
			$stopsResultsElement->SuburbName = (string)$stopElement->SuburbName;
			$stopsResults[$key] = $stopsResultsElement;
		}

		$routeData->routeNo = $this->routeNo;
		$routeData->stops = $stopsResults;
		$routeData->currentTimestamp = date('c');

		// persist to the cache file
		$routeData->save();
		return $routeData;
	}
}
?>
