<?php

DEFINE('NAMESPACE', 'http://www.yarratrams.com.au/pidsservice/');

require_once('RouteData.php');
require_once('config.php');

class ServiceData extends Persistent
{
	var $tramNumber;
	var $routeNo;
	var $offUsualRoute;
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
		global $config;

		$tramDataUrl = $config['baseApi'] . "/GetNextPredictedArrivalTimeAtStopsForTramNo/$tramNumber/?tkn=" . $config['apiToken'] . "&aid=" . $config['aid'];

		//get timeout (need to be reverted back afterwards)
		$timeout = ini_get('default_socket_timeout');
		
		// Call GetNextPredictedArrivalTimeAtStopsForTramNo ()
		$error = 0;
		
		try {
			// set a nice short timeout
			ini_set('default_socket_timeout', 1);
			
			// hit the API
		    $getTramDataRequest = curl_init($tramDataUrl);
			curl_setopt($getTramDataRequest, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($getTramDataRequest, CURLOPT_HEADER, 0);
			$jsonString = curl_exec($getTramDataRequest);
			curl_close($getTramDataRequest);
			
			$info = json_decode($jsonString);
			debugDump($info);

			//revert back
			ini_set('default_socket_timeout', $timeout);
			
		} catch (Exception $e) {
		    $error = 1;
		    $this->error = "apierror";	// don't include $fault->faultcode or $fault->faultstring
			
			//revert back
			ini_set('default_socket_timeout', $timeout);
			
			return;
		}

		if (isset($info->responseObject) && strlen($info->errorMessage) == 0)
		{
			$this->tramNumber = (string) $info->responseObject->VehicleNo;
			$this->routeNo = (string) $info->responseObject->RouteNo;
			$this->headBoardRouteNo = (string) $info->responseObject->HeadBoardRouteNo;
			$this->nextStops = $info->responseObject->NextPredictedStopsDetails;
			$this->offUsualRoute = $this->checkUsualRoute($tramClass, $this->routeNo);

			$this->currentTimestamp = time();
			$this->currentTimestampTicks = $info->timeResponded;

			$isUpDirection = ((string) $info->responseObject->Up) == '1';
			$atLayover = ((string) $info->responseObject->AtLayover) == '1';
			$available = ((string) $info->responseObject->Available) == '1';
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
		else 
		{
			$this->error = "offnetwork";
			return;
		}

		$routeNumber = $this->headBoardRouteNo;
		// need to use headBoardRouteNo for route 12 trams diverted via La Trobe Street
		// but can't use it for route 82 depot runs
		if ($routeNumber == 83)
		{
			$routeNumber = 82;
		}
		if ($routeNumber == 56)
		{
			$routeNumber = 57;
		}
		if ($routeNumber == 36)
		{
			$routeNumber = 30;
		}
		if ($routeNumber == 0)
		{
			$routeNumber = $this->routeNo;
		}

		$cacheLocation = __DIR__."/../cache/route/route" . $routeNumber . $this->direction . ".ser";

		$this->routeData = $this->loadRouteData($cacheLocation, $isUpDirection);
		$this->destination = $isUpDirection ? $this->routeData->upDirection : $this->routeData->downDirection;

		if (sizeof($this->nextStops) == 0)
		{
			$this->error = "nodata";
			return;
		}
		
		$nextStop = $this->nextStops[0];
		$currentStopNo = (string)$nextStop->StopNo;
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
		global $config;
		
		$routeData = new RouteData($cacheLocation);
		$routeData->open();

		//if no cached data exists, then load it then persist
		if(isset($routeData->headBoardRouteNo))
		{
			return $routeData;
		}

		// load route destination details
		$destinationsForRouteUrl = $config['baseApi'] . "/GetDestinationsForRoute/" . $this->headBoardRouteNo . "/?tkn=" . $config['apiToken'] . "&aid=" . $config['aid'];
		$destinationsForRouteRequest = curl_init($destinationsForRouteUrl);
		curl_setopt($destinationsForRouteRequest, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($destinationsForRouteRequest, CURLOPT_HEADER, 0);
		$destinationsForRouteString = curl_exec($destinationsForRouteRequest);
		curl_close($destinationsForRouteRequest);
		
		$destinationsForRouteInfo = json_decode($destinationsForRouteString);
		debugDump($destinationsForRouteInfo);
		
		if (isset($destinationsForRouteInfo->responseObject) && strlen($destinationsForRouteInfo->errorMessage) == 0)
		{
			$routeData->upDirection = $destinationsForRouteInfo->responseObject[0]->UpDestination;
			$routeData->downDirection = $destinationsForRouteInfo->responseObject[0]->DownDestination;
		}
		else
		{
			$this->error = "invalidroute";
			return;
		}

		// load route stop details
		$listOfStopsUrl = $config['baseApi'] . "/GetListOfStopsByRouteNoAndDirection/" . $this->headBoardRouteNo . "/" . ($isUpDirection ? "true" : "false") . "/?tkn=" . $config['apiToken'] . "&aid=" . $config['aid'];
		$listOfStopsRequest = curl_init($listOfStopsUrl);
		curl_setopt($listOfStopsRequest, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($listOfStopsRequest, CURLOPT_HEADER, 0);
		$listOfStopsString = curl_exec($listOfStopsRequest);
		curl_close($listOfStopsRequest);
		
		$listOfStopsInfo = json_decode($listOfStopsString);
		debugDump($listOfStopsInfo);

		// mash the data into an easy to load array
		$stopsResults = array();
		foreach ($listOfStopsInfo->responseObject as $stopElement)
		{
			// map data to be serialisable
			$key = (string)$stopElement->StopNo;
			$stopsResultsElement = new stdClass;
			$stopsResultsElement->Name = trim((string)$stopElement->Name);
			$stopsResultsElement->Description = trim((string)$stopElement->Description);
			$stopsResultsElement->Latitude = (string)$stopElement->Latitude;
			$stopsResultsElement->Longitude = (string)$stopElement->Longitude;
			$stopsResultsElement->SuburbName = (string)$stopElement->SuburbName;
			$stopsResults[$key] = $stopsResultsElement;
		}

		$routeData->routeNo = $this->routeNo;
		$routeData->headBoardRouteNo = $this->headBoardRouteNo;
		$routeData->stops = $stopsResults;
		$routeData->currentTimestamp = date('c');

		// persist to the cache file
		$routeData->save();
		return $routeData;
	}
}

function debugDump($info)
{
	if (isset($_GET['dump']) == 1)
	{
		echo "<pre>";
		print_r($info );
		echo "</pre>";
	}
}
?>
