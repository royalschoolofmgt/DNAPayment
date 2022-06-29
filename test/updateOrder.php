<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');
require_once('store_helper.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$data = file_get_contents('php://input');
// create a log channel
$logger = new Logger('PostLink Update Order');
$logger->pushHandler(new StreamHandler('var/logs/DNA_update_order.txt', Logger::INFO));
$logger->info("PostLink Callback data: ".$data);
							
if(!empty($data)){
	$data = json_decode($data,true);
	//print_r($data);exit;	
	if(isset($data['success']) && $data['success']){
		$conn = getConnection();
		$invoiceId = $data['invoiceId'];
		$usql = 'update order_payment_details set status = ?,api_response=? where order_id=?';
		$stmt = $conn->prepare($usql);
		$stmt->execute(["CONFIRMED",addslashes(json_encode($data)),$invoiceId]);
		
		$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id=?");
		$stmt_order_payment->execute([$invoiceId]);
		$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
		$result_order_payment = $stmt_order_payment->fetchAll();
		//print_r($result_order_payment);exit;
		if (isset($result_order_payment[0])) {
			$result_order_payment = $result_order_payment[0];
			
			$string = base64_decode($result_order_payment['params']);
			$string = preg_replace("/[\r\n]+/", " ", $string);
			$json = utf8_encode($string);
			$cartData = json_decode($json,true);
			$items_total = 0;
			$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
			$stmt->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id']]);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$result = $stmt->fetchAll();
			//print_r($result[0]);exit;
			if (isset($result[0])) {
				$result = $result[0];
				$acess_token = $result['acess_token'];
				$store_hash = $result['store_hash'];
				
				$customer_email_id = @$cartData['cart']['email'];
				$customer_id = @$cartData['cart']['customer_id'];
				$merchant_card_token = @$data['cardTokenId'];
				$merchant_card_name = @$data['cardholderName'];
				$merchant_card_schema = @$data['cardSchemeName'];
				if(empty($merchant_card_name)){
					$merchant_card_name = $merchant_card_schema;
				}
				if($customer_id > 0 && !empty($merchant_card_token)){
					try{
						$stmt_card_token = $conn->prepare("select * from card_token where email_id=? and token_validation_id=? and customer_id=? and customer_email_id=? and merchant_card_token=?");
						
						$stmt_card_token->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id'],$customer_id,$customer_email_id,$merchant_card_token]);
						$stmt_card_token->setFetchMode(PDO::FETCH_ASSOC);
						$result_card_token = $stmt_card_token->fetchAll();
						//print_r($result[0]);exit;
						if (isset($result_card_token[0])) {
						}else{
							$card_token_sql = 'insert into card_token(email_id,token_validation_id,customer_id,customer_email_id,merchant_card_token,merchant_card_name,merchant_card_schema) values(?,?,?,?,?,?,?)';
					
							$stmt_card_token_insert = $conn->prepare($card_token_sql);
							$stmt_card_token_insert->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id'],$customer_id,$customer_email_id,$merchant_card_token,$merchant_card_name,$merchant_card_schema]);
						}
					}catch(\Exception $e){
						
					}
				}
				
				$order_products = array();
				foreach($cartData['cart']['line_items'] as $liv){
					$cart_products = $liv;
					foreach($cart_products as $k=>$v){
						if($v['variant_id'] > 0){
							$details = array();
							$productOptions = productOptions($acess_token,$store_hash,$result_order_payment['email_id'],$v['product_id'],$v['variant_id'],$result_order_payment['token_validation_id']);

							$logger->info("Product variant options: ".json_encode($productOptions));

							$temp_option_values = $productOptions['option_values'];
							$option_values = array();
							if(!empty($temp_option_values) && isset($temp_option_values[0])){
								foreach($temp_option_values as $tk=>$tv){
									$option_values[] = array(
													"id" => $tv['option_id'],
													"value" => strval($tv['id'])
												);
								}
							}else{
								if(isset($v['options']) && !empty($v['options'])){
									foreach($v['options'] as $tk=>$tv){
										if(isset($tv['name_id']) && isset($tv['value_id'])){
											$option_values[] = array(
														"id" => $tv['name_id'],
														"value" => strval($tv['value_id'])
													);
										}
									}
								}
							}
							$items_total += $v['quantity'];
							$details = array(
											"product_id" => $v['product_id'],
											"quantity" => $v['quantity'],
											"product_options" => $option_values,
											"price_inc_tax" => $v['sale_price'],
											"price_ex_tax" => $v['sale_price'],
											"upc" => @$productOptions['upc'],
											"variant_id" => $v['variant_id']
										);
							$order_products[] = $details;
						}
					}
				}
				
				//print_r($order_products);exit;
				$checkShipping = false;
				if(count($cartData['cart']['line_items']['physical_items']) > 0 || count($cartData['cart']['line_items']['custom_items']) > 0){
					$checkShipping = true;
				}else{
					if(count($cartData['cart']['line_items']['digital_items']) > 0){
						$checkShipping = false;
					}
				}
				$cart_billing_address = $cartData['billing_address'];
				$billing_address = array(
										"first_name" => $cart_billing_address['first_name'],
										"last_name" => $cart_billing_address['last_name'],
										"phone" => $cart_billing_address['phone'],
										"email" => $cart_billing_address['email'],
										"street_1" => $cart_billing_address['address1'],
										"street_2" => $cart_billing_address['address2'],
										"city" => $cart_billing_address['city'],
										"state" => $cart_billing_address['state_or_province'],
										"zip" => $cart_billing_address['postal_code'],
										"country" => $cart_billing_address['country'],
										"company" => $cart_billing_address['company']
									);
				if($checkShipping){
					$cart_shipping_address = $cartData['consignments'][0]['shipping_address'];
					$cart_shipping_options = $cartData['consignments'][0]['selected_shipping_option'];
					$shipping_address = array(
											"first_name" => $cart_shipping_address['first_name'],
											"last_name" => $cart_shipping_address['last_name'],
											"company" => $cart_shipping_address['company'],
											"street_1" => $cart_shipping_address['address1'],
											"street_2" => $cart_shipping_address['address2'],
											"city" => $cart_shipping_address['city'],
											"state" => $cart_shipping_address['state_or_province'],
											"zip" => $cart_shipping_address['postal_code'],
											"country" => $cart_shipping_address['country'],
											"country_iso2" => $cart_shipping_address['country_code'],
											"phone" => $cart_shipping_address['phone'],
											"email" => $cart_billing_address['email'],
											"shipping_method" => $cart_shipping_options['description']
										);
				}
				$createOrder = array();
				$createOrder['customer_id'] = $cartData['cart']['customer_id'];
				$createOrder['products'] = $order_products;
				if($checkShipping){
					$createOrder['shipping_addresses'][] = $shipping_address;
				}
				$createOrder['billing_address'] = $billing_address;
				if(isset($cartData['coupons'][0]['discounted_amount'])){
					$createOrder['discount_amount'] = $cartData['coupons'][0]['discounted_amount'];
				}
				$createOrder['customer_message'] = $cartData['customer_message'];
				$createOrder['customer_locale'] = "en";
				$createOrder['total_ex_tax'] = $cartData['grand_total'];
				$createOrder['total_inc_tax'] = $cartData['grand_total'];
				$createOrder['geoip_country'] = $cart_billing_address['country'];
				$createOrder['geoip_country_iso2'] = $cart_billing_address['country_code'];
				//$createOrder['status_id'] = 11;
				$createOrder['ip_address'] = get_client_ip();
				if($checkShipping){
					$createOrder['order_is_digital'] = true;
				}
				$createOrder['shipping_cost_ex_tax'] = $cartData['shipping_cost_total_ex_tax'];
				$createOrder['shipping_cost_inc_tax'] = $cartData['shipping_cost_total_inc_tax'];
				
				/*$createOrder['subtotal_ex_tax'] = $cartData['subtotal'];
				$createOrder['subtotal_inc_tax'] = $cartData['subtotal'];
				$createOrder['base_shipping_cost'] = $cartData['subtotal'];
				$createOrder['shipping_cost_ex_tax'] = $cartData['shippingCostTotal'];
				$createOrder['shipping_cost_inc_tax'] = $cartData['shippingCostTotal'];
				$createOrder['base_handling_cost'] = $cartData['handlingCostTotal'];
				$createOrder['handling_cost_ex_tax'] = $cartData['handlingCostTotal'];
				$createOrder['handling_cost_inc_tax'] = $cartData['handlingCostTotal'];
				$createOrder['total_ex_tax'] = $cartData['grandTotal'];
				$createOrder['total_inc_tax'] = $cartData['grandTotal'];
				$createOrder['items_total'] = $items_total;
				$createOrder['items_shipped'] = $items_total;
				$createOrder['payment_provider_id'] = "";
				$createOrder['refunded_amount'] = "";
				$createOrder['order_is_digital'] = true;
				$createOrder['ip_address'] = get_client_ip();
				$geoData = getGeoData();
				$createOrder['geoip_country'] = "India";
				$createOrder['geoip_country_iso2'] = $geoData['country'];
				$createOrder['staff_notes'] = "";*/
				$createOrder['tax_provider_id'] = "BasicTaxProvider";
				$createOrder['payment_method'] = "DNA PAYMENTS";
				$createOrder['external_source'] = "247 DNA";
				$createOrder['status_id'] = 0;
				$createOrder['default_currency_code'] = $cartData['cart']['currency']['code'];
				
				$logger->info("Before create order API call");
				$bigComemrceOrderId = createOrder($acess_token,$store_hash,$result_order_payment['email_id'],$createOrder,$invoiceId,$result_order_payment['token_validation_id']);

				$logger->info("Create order API response: ".$bigComemrceOrderId);

				if($bigComemrceOrderId != "") {
					updateBCCustomerStoreCredit($result_order_payment['email_id'], $result_order_payment['token_validation_id'], $invoiceId)
					//update order status for trigger status update mail from bigcommerce
					$logger->info("Before update order status API call");
					$statusResponse = updateOrderStatus($bigComemrceOrderId, $acess_token, $store_hash, $result_order_payment['email_id'],$result_order_payment['token_validation_id']);

					$logger->info("Update order status API response: ".$statusResponse);
				}

				$logger->info("Before delete cart API call");
				$delCartResponse = deleteCart($acess_token,$store_hash,$result_order_payment['email_id'],$result_order_payment['cart_id'],$result_order_payment['token_validation_id']);

				$logger->info("delete cart API response: ".$delCartResponse);
				
			}
		}
	}
}

function productOptions($acess_token,$store_hash,$email_id,$productId,$variantId,$token_validation_id){
	$data = array();
	
	$conn = getConnection();
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$url = STORE_URL.$store_hash.'/v3/catalog/products/'.$productId.'/variants';
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res = curl_exec($ch);
	curl_close($ch);
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
	$stmt = $conn->prepare($log_sql);
	$stmt->execute([$email_id,"BigCommerce","Product Options",addslashes($url),"",addslashes($res),$token_validation_id]);
	if(!empty($res)){
		$res = json_decode($res,true);
		if(isset($res['data'])){
			$res = $res['data'];
			if(count($res) > 0){
				foreach($res as $k=>$v){
					if($v['id'] == $variantId){
						$data = $v;
						break;
					}
				}
			}
		}
	}
	return $data;
}

function deleteCart($acess_token,$store_hash,$email_id,$cart_id,$token_validation_id){
	$res = "";
	$conn = getConnection();
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$url = STORE_URL.$store_hash.'/v3/carts/'.$cart_id;
	$request = '';
	//echo $request;exit;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"DELETE");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res = curl_exec($ch);
	curl_close($ch);
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
	$stmt = $conn->prepare($log_sql);
	$stmt->execute([$email_id,"BigCommerce","Clear Cart",addslashes($url),addslashes($request),addslashes($res),$token_validation_id]);

	return $res;
	
}

function createOrder($acess_token,$store_hash,$email_id,$request,$invoiceId,$token_validation_id){
	$bigComemrceOrderId = "";
	$conn = getConnection();
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$url = STORE_URL.$store_hash.'/v2/orders';
	$request = json_encode($request);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res = curl_exec($ch);
	curl_close($ch);
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
	$stmt = $conn->prepare($log_sql);
	$stmt->execute([$email_id,"BigCommerce","Create Order",addslashes($url),addslashes($request),addslashes($res),$token_validation_id]);
	
	if(!empty($res)){
		$res = json_decode($res,true);
		if(isset($res['id'])){
			$isql = "INSERT INTO `order_details` (`email_id`, `invoice_id`, `order_id`, `bg_customer_id`, `reponse_params`, `total_inc_tax`, `total_ex_tax`, `currecy`,token_validation_id) VALUES (?,?,?,?,?,?,?,?,?)";
			$stmt= $conn->prepare($isql);
			$stmt->execute([$email_id, $invoiceId, $res['id'],$res['customer_id'],addslashes(json_encode($res)),$res['total_inc_tax'],$res['total_ex_tax'],$res['currency_code'],$token_validation_id]);

			$bigComemrceOrderId = $res['id'];
		}
	}

	return $bigComemrceOrderId;
}

function updateOrderStatus($bigComemrceOrderId,$acess_token,$store_hash,$email_id,$token_validation_id) {
	$conn = getConnection();
	$url_u = STORE_URL.$store_hash.'/v2/orders/'.$bigComemrceOrderId;
	$request_u = array("status_id"=>11);
	$request_u = json_encode($request_u,true);
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_u);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_u);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res_u = curl_exec($ch);
	curl_close($ch);
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
	$stmt= $conn->prepare($log_sql);
	$stmt->execute([$email_id, "BigCommerce", "Update Order",addslashes($url_u),addslashes($request_u),addslashes($res_u),$token_validation_id]);

	return $res_u;
}

function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
	$ip = explode(",",$ipaddress);
	if(isset($ip[0])){
		$ipaddress = $ip[0];
	}
    return $ipaddress;
}

function getGeoData(){
	$PublicIP = get_client_ip();
	$PublicIP = explode(",",$PublicIP);
	$json     = file_get_contents("http://ipinfo.io/$PublicIP[0]/geo");
	$json     = json_decode($json, true);
	return $json;
}
?>