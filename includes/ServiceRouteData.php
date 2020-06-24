<?php

require_once('Persistent.php');
require_once('ServiceData.php');

class ServiceRouteData extends Persistent
{
	var $tramNumber;
	var $routeNo;
	var $offUsualRoute;
	var $destination;
	var $direction;
	var $currentTimestamp;
	var $currentLat;
	var $currentLon;
	var $error;

	function __construct($tramNumber, $tramClass, $forceRefresh)
	{
		// Enable persistence if required
		parent::__construct("cache/service/tram$tramNumber.ser");

		// try to load data
		$this->open();

		// if something found, then skip everything, unless a refresh is being forced
		if(isset($this->tramNumber) && !$forceRefresh)
		{
			return $this;
		}

		// Now the heavy lifting
		$serviceData = new ServiceData($tramNumber, $tramClass);
		
		if(isset($serviceData->error) && $serviceData->error == 'apierror')
		{
			$this->error = $serviceData->error;
			return $this;
		}

		// Map the data back
		$this->tramNumber = $serviceData->tramNumber;
		$this->routeNo = $serviceData->routeNo;
		$this->offUsualRoute = $serviceData->offUsualRoute;
		$this->destination = $serviceData->destination;
		$this->direction = $serviceData->direction;
		$this->currentTimestamp = $serviceData->currentTimestamp;
		$this->currentLat = $serviceData->currentLat;
		$this->currentLon = $serviceData->currentLon;
		$this->error = $serviceData->error;

		// Persist to the cache
		$this->save();
		return $this;
	}
}
