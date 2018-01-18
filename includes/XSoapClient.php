<?php

class XSoapClient extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
	    return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}

?>