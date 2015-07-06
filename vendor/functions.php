<?php


error_reporting(E_ALL ^ E_NOTICE);
function prepareMoneyInfo($str) {
	return mb_convert_encoding($str, "UTF-8", "ISO-8859-15");
}

function invokeGetPurchaseContract(OffAmazonPaymentsService_Interface $service, $request) {
    try {

        $response = $service->getOrderReferenceDetails($request);                 
		$xml = new DOMDocument();
		$xml->loadXML($response->toXML());

		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$data = $xml->saveXML();  
		$data = simplexml_load_string($data);
		return $data;

    } catch (Exception $ex) {
    //} catch (OffAmazonPaymentsService_Exception $ex) {
		var_dump($ex);
    
      if ($_SESSION['customers_status']['customers_status_id'] == 0) {
        echo 'Diese Fehlermeldung sehen Sie nur als Admin: <br/>';
        echo("Caught Exception: " . $ex->getMessage() . "<br/>");
        echo("Response Status Code: " . $ex->getStatusCode() . "<br/>");
        echo("Error Code: " . $ex->getErrorCode() . "<br/>");
        echo("Error Type: " . $ex->getErrorType() . "<br/>");
        echo("Request ID: " . $ex->getRequestId() . "<br/>");
        echo("XML: " . $ex->getXML() . "<br/>");
      }
		return false;
    }
}

function invokeSetPurchaseItems(OffAmazonPaymentsService_Interface $service, $request) {
	try {
		$response = $service->setPurchaseItems($request);
		$xml = new DOMDocument();
		$xml->loadXML($response->toXML());

		// these lines would create a nicely indented XML file
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
                $data = $xml->saveXML();  
		$data = simplexml_load_string($data);
                return $data;
        

	} catch (OffAmazonPaymentsService_Exception $ex) {
		if ($_SESSION['customers_status']['customers_status_id'] == 0) {
       echo 'Diese Fehlermeldung sehen Sie nur als Admin: <br/>';
       echo("Caught Exception: " . $ex->getMessage() . "\n");
       echo("Response Status Code: " . $ex->getStatusCode() . "\n");
       echo("Error Code: " . $ex->getErrorCode() . "\n");
       echo("Error Type: " . $ex->getErrorType() . "\n");
       echo("Request ID: " . $ex->getRequestId() . "\n");
       echo("XML: " . $ex->getXML() . "\n");
		}
     return false;
	}
}

function invokeSetContractCharges(OffAmazonPaymentsService_Interface $service, $request) {
	 
    try {
                $response = $service->setContractCharges($request);

                $xml = new DOMDocument();
                $xml->loadXML($response->toXML());

                // these lines would create a nicely indented XML file
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
                $data = $xml->saveXML();  
                $data = simplexml_load_string($data);
                return $data;
			    

	 } catch (OffAmazonPaymentsService_Exception $ex) {
   
      if ($_SESSION['customers_status']['customers_status_id'] == 0) {
        echo 'Diese Fehlermeldung sehen Sie nur als Admin: <br/>';
        echo("Caught Exception: " . $ex->getMessage() . "\n");
        echo("Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("Error Code: " . $ex->getErrorCode() . "\n");
        echo("Error Type: " . $ex->getErrorType() . "\n");
        echo("Request ID: " . $ex->getRequestId() . "\n");
        echo("XML: " . $ex->getXML() . "\n");
      }
		 return false;
	 }
}

function invokeCompletePurchaseContract(OffAmazonPaymentsService_Interface $service, $request) {
  try {
  $response = $service->completePurchaseContract($request);
  
	$xml = new DOMDocument();
	$xml->loadXML($response->toXML());

	// these lines would create a nicely indented XML file
	$xml->preserveWhiteSpace = false;
	$xml->formatOutput = true;
        $data = $xml->saveXML();  
	$data = simplexml_load_string($data);
        return $data;


 } catch (OffAmazonPaymentsService_Exception $ex) {
 
   if ($_SESSION['customers_status']['customers_status_id'] == 0) {
     echo 'Diese Fehlermeldung sehen Sie nur als Admin: <br/>';
     echo("Caught Exception: " . $ex->getMessage() . "\n");
     echo("Response Status Code: " . $ex->getStatusCode() . "\n");
     echo("Error Code: " . $ex->getErrorCode() . "\n");
     echo("Error Type: " . $ex->getErrorType() . "\n");
     echo("Request ID: " . $ex->getRequestId() . "\n");
     echo("XML: " . $ex->getXML() . "\n");
   }
	 return false;
 }
}               

				

function invokeConfirmPurchaseContract(OffAmazonPaymentsService_Interface $service, $request) {
  try {
        $response = $service->ConfirmOrderReference($request);
  
	$xml = new DOMDocument();
	$xml->loadXML($response->toXML());

	// these lines would create a nicely indented XML file
	$xml->preserveWhiteSpace = false;
	$xml->formatOutput = true;
        $data = $xml->saveXML();  
	$data = simplexml_load_string($data);
        return $data;


 } catch (OffAmazonPaymentsService_Exception $ex) {
 
   if ($_SESSION['customers_status']['customers_status_id'] == 0) {
     echo 'Diese Fehlermeldung sehen Sie nur als Admin: <br/>';
     echo("Caught Exception: " . $ex->getMessage() . "\n");
     echo("Response Status Code: " . $ex->getStatusCode() . "\n");
     echo("Error Code: " . $ex->getErrorCode() . "\n");
     echo("Error Type: " . $ex->getErrorType() . "\n");
     echo("Request ID: " . $ex->getRequestId() . "\n");
     echo("XML: " . $ex->getXML() . "\n");
   }
	 return false;
 }
} 