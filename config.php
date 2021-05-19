<?php
/**
	* Main Config
	* Author 247Commerce
	* Date 22 FEB 2021
*/
define('BASE_URL','https://'.$_SERVER['HTTP_HOST'].'/');
define('APP_CLIENT_ID','cf8fjrtd0xpj6et34no5fdzdsmlxqh');
define('APP_CLIENT_SECRET','488d0137e03c8d6149d8c41ed47ab506adcda968336da7f24a5f65271d619720');
define('STORE_URL','https://api.bigcommerce.com/stores/');

define('AUTHENTICATE_URL','https://test-oauth.dnapayments.com/oauth2/token');//test
define('REFUND_URL','https://test-api.dnapayments.com/transaction/operation/refund');//test
define('SETTLE_URL','https://test-api.dnapayments.com/transaction/operation/charge');//test


define('JS_SDK','https://code.jquery.com/jquery-3.5.1.js');
define('DNA_SDK','https://pay.dnapayments.com/checkout/payment-api.js');
?>