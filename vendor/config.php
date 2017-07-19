<?php
	/*
    define('AWS_ACCESS_KEY_ID', MODULE_PAYMENT_RMAMAZON_ACCESKEY);
    define('AWS_SECRET_ACCESS_KEY', MODULE_PAYMENT_RMAMAZON_SECRETKEY);
	define('AWS_MERCHANT_ID', MODULE_PAYMENT_RMAMAZON_MERCHANTID);
    define('MODULE_PAYMENT_RMAMAZON_MARKETPLACEID', 'A1OCY9REWJOCW5');
	*/

    set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path() . PATH_SEPARATOR);
    
    spl_autoload_register('amazonAutoLoader');
    
 	function amazonAutoLoader($className){ 		
        $filePath = '/' . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        $includePaths = explode(PATH_SEPARATOR, get_include_path());
        foreach($includePaths as $includePath){ 
            if (strpos($includePath, 'amzpayments') !== false) {
			    if(file_exists($includePath . $filePath)){
			         require_once $includePath . $filePath;
                     return;
                }
            }
        }
    }
