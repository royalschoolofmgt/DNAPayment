<?php
/**
	* Main Config
	* Author 247Commerce
	* Date 22 FEB 2021
*/
define('BASE_URL','http://'.$_SERVER['HTTP_HOST'].'/dna_pay/');
define('APP_CLIENT_ID','dfmq77mlupedzl997j359io7sv6ow49');
define('APP_CLIENT_SECRET','2fa21093ad9b65bca8f1a7c2ecd528b8fbec84c39206b0e2f9039d7990140e64');
define('STORE_URL','https://api.bigcommerce.com/stores/');

define('AUTHENTICATE_URL','https://test-oauth.dnapayments.com/oauth2/token');//test
define('REFUND_URL','https://test-api.dnapayments.com/transaction/operation/refund');//test
define('SETTLE_URL','https://test-api.dnapayments.com/transaction/operation/charge');//test


define('JS_SDK','https://code.jquery.com/jquery-3.5.1.js');
//define('DNA_SDK','https://test-pay.dnapayments.com/checkout/payment-api.js');
define('DNA_SDK',BASE_URL.'/js/payment-api.js');
?>