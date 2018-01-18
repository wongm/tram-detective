<?php

require_once('Persistent.php');
require_once('ServiceData.php');

class ServiceRouteData extends Persistent 
{
	var $vehicleNo;
	var $routeNo;
	var $destination;
	var $direction;
	var $currentTimestamp;
	var $currentLat;
	var $currentLon;
	var $error;
	
	function ServiceRouteData($vehicleNo, $forceRefresh)
	{
		// Enable persistance if required
		parent::__construct("cache/service/tram$vehicleNo.ser");
		
		// try to load data
		$this->open();
		
		// if something found, then skip everything, unless a refresh is being forced
		if(isset($this->vehicleNo) && !$forceRefresh)
		{
			return $this;
		}
		
		// Now the heavy lifting
		$serviceData = new ServiceData($vehicleNo);
		
		// Map the data back
		$this->vehicleNo = $serviceData->vehicleNo;
		$this->routeNo = $serviceData->routeNo;
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