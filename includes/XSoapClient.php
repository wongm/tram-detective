<?php

class XSoapClient extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
	    $method = str_replace(NAMESPACE, '', $action);
	    
	    // add required namespaces to header and body
	    $request = str_replace('<ns1:PidsClientHeader>', '<PidsClientHeader xmlns="' . NAMESPACE . '">', $request);
	    $request = str_replace('<ns1:' . $method . '>', '<' . $method . ' xmlns="' . NAMESPACE . '">', $request);
	    
	    // get rid of unneeded namespaces
	    $request = str_replace('SOAP-ENV', 'soap', $request);
	    $request = str_replace('ns1:', '', $request);
	    $request = str_replace('ns2:', '', $request);
	    $request = str_replace('ns2:', '', $request);
	    
	    // fix XML declaration too
	    $request = preg_replace('<soap:Envelope [^<>]*>', 'soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"', $request, -1);
	    
	    return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}

?>