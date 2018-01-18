<?php
require_once('Persistent.php');

class RouteData extends Persistent 
{ 
	var $routeNo;
	var $timestamp;
	var $stops;
	var $upDirection;
	var $downDirection;
} 