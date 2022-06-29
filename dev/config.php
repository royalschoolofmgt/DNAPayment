<?php
/**
	* Main Config
	* Author 247Commerce
	* Date 22 FEB 2021
*/
define('BASE_URL','https://'.$_SERVER['HTTP_HOST'].'/dev/');
define('APP_CLIENT_ID','28y2jbvyhcpse32bzu0oposnpsmrdap');
define('APP_CLIENT_SECRET','98082c1c288b3a2ad26728d2e906fd19fb7a0d1f79496703ebc288ecce02c1cb');
define('STORE_URL','https://api.bigcommerce.com/stores/');
define('ENVIRONMENT','prod');

if(ENVIRONMENT == "prod"){
	define('AUTHENTICATE_URL','https://oauth.dnapayments.com/oauth2/token');//Live
	define('REFUND_URL','https://api.dnapayments.com/transaction/operation/refund');//Live
	define('SETTLE_URL','https://api.dnapayments.com/transaction/operation/charge');//Live
	define('CANCEL_URL','https://api.dnapayments.com/transaction/operation/cancel');//Live
}else{
	define('AUTHENTICATE_URL','https://test-oauth.dnapayments.com/oauth2/token');//test
	define('REFUND_URL','https://test-api.dnapayments.com/transaction/operation/refund');//test
	define('SETTLE_URL','https://test-api.dnapayments.com/transaction/operation/charge');//test
	define('CANCEL_URL','https://test-api.dnapayments.com/transaction/operation/cancel');//test
}

define('AUTHENTICATE_URL_TEST','https://test-oauth.dnapayments.com/oauth2/token');//test
define('REFUND_URL_TEST','https://test-api.dnapayments.com/transaction/operation/refund');//test
define('SETTLE_URL_TEST','https://test-api.dnapayments.com/transaction/operation/charge');//test
define('CANCEL_URL_TEST','https://test-api.dnapayments.com/transaction/operation/cancel');//test


define('JS_SDK','https://code.jquery.com/jquery-3.5.1.js');
define('DNA_SDK','https://pay.dnapayments.com/checkout/payment-api.js');
?>