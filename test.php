<?php    
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

$store_hash = "v6q95r5n91";
$acess_token = "cx3442apqcnsel6c43rewb1wcjkjche";
$email_id = 'bigi@247commerce.co.uk';
$invoiceId = '247dna_1614688247';
$res = '{"id":125,"customer_id":3,"date_created":"Tue, 02 Mar 2021 12:31:11 +0000","date_modified":"Tue, 02 Mar 2021 12:31:11 +0000","date_shipped":"","status_id":1,"status":"Pending","subtotal_ex_tax":"49.9900","subtotal_inc_tax":"49.9900","subtotal_tax":"0.0000","base_shipping_cost":"0.0000","shipping_cost_ex_tax":"0.0000","shipping_cost_inc_tax":"0.0000","shipping_cost_tax":"0.0000","shipping_cost_tax_class_id":0,"base_handling_cost":"0.0000","handling_cost_ex_tax":"0.0000","handling_cost_inc_tax":"0.0000","handling_cost_tax":"0.0000","handling_cost_tax_class_id":0,"base_wrapping_cost":"0.0000","wrapping_cost_ex_tax":"0.0000","wrapping_cost_inc_tax":"0.0000","wrapping_cost_tax":"0.0000","wrapping_cost_tax_class_id":0,"total_ex_tax":"49.9900","total_inc_tax":"49.9900","total_tax":"0.0000","items_total":1,"items_shipped":0,"payment_method":"247 DNA","payment_provider_id":null,"payment_status":"","refunded_amount":"0.0000","order_is_digital":false,"store_credit_amount":"0.0000","gift_certificate_amount":"0.0000","ip_address":"","geoip_country":"","geoip_country_iso2":"","currency_id":2,"currency_code":"USD","currency_exchange_rate":"1.0000000000","default_currency_id":2,"default_currency_code":"USD","staff_notes":null,"customer_message":null,"discount_amount":"0.0000","coupon_discount":"0.0000","shipping_address_count":1,"is_deleted":false,"ebay_order_id":"0","cart_id":null,"billing_address":{"first_name":"Vilas","last_name":"Krish","company":"247Commerce","street_1":"Bangalore","street_2":"","city":"Bangalore","state":"Karnataka","zip":"560100","country":"India","country_iso2":"IN","phone":"7894561230","email":"vilas@247commerce.co.uk","form_fields":[]},"is_email_opt_in":false,"credit_card_type":null,"order_source":"external","channel_id":1,"external_source":"247 DNA","products":{"url":"https:\/\/api.bigcommerce.com\/stores\/v6q95r5n91\/v2\/orders\/125\/products","resource":"\/orders\/125\/products"},"shipping_addresses":{"url":"https:\/\/api.bigcommerce.com\/stores\/v6q95r5n91\/v2\/orders\/125\/shipping_addresses","resource":"\/orders\/125\/shipping_addresses"},"coupons":{"url":"https:\/\/api.bigcommerce.com\/stores\/v6q95r5n91\/v2\/orders\/125\/coupons","resource":"\/orders\/125\/coupons"},"external_id":null,"external_merchant_id":null,"tax_provider_id":"","customer_locale":"","store_default_currency_code":"USD","store_default_to_transactional_exchange_rate":"1.0000000000","custom_status":"Pending"}';
if(!empty($res)){
		$res = json_decode($res,true);
		if(isset($res['id'])){
			$isql = "INSERT INTO `order_details` (`email_id`, `invoice_id`, `order_id`, `bg_customer_id`, `reponse_params`, `total_inc_tax`, `total_ex_tax`, `currecy`) VALUES ('".$email_id."', '".$invoiceId."', '".$res['id']."', '".$res['customer_id']."', '".addslashes(json_encode($res))."', '".$res['total_inc_tax']."', '".$res['total_ex_tax']."', '".$res['currency_code']."')";
			$conn = getConnection();
			$conn->exec($isql);
		}
	}
?>