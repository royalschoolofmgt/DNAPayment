<?php
/**
	* Main Config
	* Author 247Commerce
	* Date 22 FEB 2021
*/
define('BASE_URL','https://'.$_SERVER['HTTP_HOST'].'/dna_payment/');
define('APP_CLIENT_ID','dgl1obxzmmjkez234p8s4iai8rpaqqa');
define('APP_CLIENT_SECRET','2ebb7537ee087630facd5fff22b5245441c631e0ad20a1cc610f9ef1bce6e5da');
define('STORE_URL','https://api.bigcommerce.com/stores/');

define('AUTHENTICATE_URL','https://test-oauth.dnapayments.com/oauth2/token');//test


define('JS_SDK','https://code.jquery.com/jquery-3.5.1.js');
//define('DNA_SDK','https://test-pay.dnapayments.com/checkout/payment-api.js');
define('DNA_SDK',BASE_URL.'/js/payment-api.js');
?>