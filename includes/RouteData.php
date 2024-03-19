<?php
require_once('Persistent.php');

class RouteData extends Persistent 
{ 
	var $routeNo;
	var $headBoardRouteNo;
	var $timestamp;
	var $currentTimestamp;
	var $stops;
	var $upDirection;
	var $downDirection;
} 