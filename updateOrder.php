<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

$data = file_get_contents('php://input');
try{
	$fp = fopen("order.txt", "w");
	fwrite($fp, $data);
	fclose($fp);
}catch(Exception $e) {
}

if(!empty($data)){
	$data = json_decode($data,true);
	if(isset($data['success']) && $data['success']){
		$conn = getConnection();
		$invoiceId = $data['invoiceId'];
		$usql = 'update order_payment_details set status = "CONFIRMED",api_response="'.addslashes(json_encode($data)).'" where order_id="'.$invoiceId.'"';
		$stmt = $conn->prepare($usql);
		$stmt->execute();
		
		$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id='".$invoiceId."'");
		$stmt_order_payment->execute();
		$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
		$result_order_payment = $stmt_order_payment->fetchAll();
		if (isset($result_order_payment[0])) {
			$result_order_payment = $result_order_payment[0];
			
			$string = base64_decode($result_order_payment['params']);
			$string = preg_replace("/[\r\n]+/", " ", $string);
			$json = utf8_encode($string);
			$cartData = json_decode($json,true);
			$items_total = 0;
			$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$result_order_payment['email_id']."'");
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$result = $stmt->fetchAll();
			//print_r($result[0]);exit;
			if (isset($result[0])) {
				$result = $result[0];
				$acess_token = $result['acess_token'];
				$store_hash = $result['store_hash'];
						
				$cart_shipping_address = $cartData['billingAddress'];
				$cart_shipping_options = $cartData['consignments'][0]['selectedShippingOption'];
				$shipping_address = array(
										"first_name" => $cart_shipping_address['firstName'],
										"last_name" => $cart_shipping_address['lastName'],
										"company" => $cart_shipping_address['company'],
										"street_1" => $cart_shipping_address['address1'],
										"street_2" => $cart_shipping_address['address2'],
										"city" => $cart_shipping_address['city'],
										"state" => $cart_shipping_address['stateOrProvince'],
										"zip" => $cart_shipping_address['postalCode'],
										"country" => $cart_shipping_address['country'],
										"country_iso2" => $cart_shipping_address['countryCode'],
										"phone" => $cart_shipping_address['phone'],
										"email" => $cart_shipping_address['email'],
										"shipping_method" => $cart_shipping_options['type']
									);
				$billing_address = array(
										"first_name" => $cart_shipping_address['firstName'],
										"last_name" => $cart_shipping_address['lastName'],
										"phone" => $cart_shipping_address['phone'],
										"email" => $cart_shipping_address['email'],
										"street_1" => $cart_shipping_address['address1'],
										"street_2" => $cart_shipping_address['address2'],
										"city" => $cart_shipping_address['city'],
										"state" => $cart_shipping_address['stateOrProvince'],
										"zip" => $cart_shipping_address['postalCode'],
										"country" => $cart_shipping_address['country'],
										"company" => $cart_shipping_address['company']
									);
				$cart_products = $cartData['cart']['lineItems']['physicalItems'];
				$order_products = array();
				
				foreach($cart_products as $k=>$v){
					if($v['variantId'] > 0){
						$details = array();
						$productOptions = productOptions($acess_token,$store_hash,$result_order_payment['email_id'],$v['productId'],$v['variantId']);
						$temp_option_values = $productOptions['option_values'];
						$option_values = array();
						if(!empty($temp_option_values) && isset($temp_option_values[0])){
							$option_values[] = array(
												"id" => $temp_option_values[0]['id'],
												"value" => $temp_option_values[0]['option_id']
											);
						}
						$items_total += $v['quantity'];
						$details = array(
										"product_id" => $v['productId'],
										"quantity" => $v['quantity'],
										"product_options" => $option_values,
										"price_inc_tax" => $v['salePrice'],
										"price_ex_tax" => $v['salePrice'],
										"upc" => @$productOptions['upc'],
										"variant_id" => $v['variantId']
									);
						$order_products[] = $details;
					}
				}
				$createOrder = array();
				$createOrder['customer_id'] = $cartData['cart']['customerId'];
				$createOrder['products'] = $order_products;
				$createOrder['shipping_addresses'][] = $shipping_address;
				$createOrder['billing_address'] = $billing_address;
				
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
				$createOrder['staff_notes'] = "";
				$createOrder['customer_message'] = $cartData['customerMessage'];
				$createOrder['discount_amount'] = "";
				$createOrder['tax_provider_id'] = "BasicTaxProvider";
				$createOrder['default_currency_code'] = $cartData['cart']['currency']['code'];*/
				$createOrder['payment_method'] = "247 DNA";
				$createOrder['external_source'] = "247 DNA";
				$createOrder['default_currency_code'] = $cartData['cart']['currency']['code'];
				
				createOrder($acess_token,$store_hash,$result_order_payment['email_id'],$createOrder,$invoiceId);
				deleteCart($acess_token,$store_hash,$result_order_payment['email_id'],$result_order_payment['cart_id']);
				
			}
		}
	}
}

function productOptions($acess_token,$store_hash,$email_id,$productId,$variantId){
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
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Product Options","'.addslashes($url).'","","'.addslashes($res).'")';
	//echo $log_sql;exit;
	$conn->exec($log_sql);
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
function deleteCart($acess_token,$store_hash,$email_id,$cart_id){
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
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Clear Cart","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
	//echo $log_sql;exit;
	$conn->exec($log_sql);
}
function createOrder($acess_token,$store_hash,$email_id,$request,$invoiceId){
	$conn = getConnection();
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$url = STORE_URL.$store_hash.'/v2/orders';
	$request = json_encode($request);
	//echo $request;exit;
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
	
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Create Order","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
	//echo $log_sql;exit;
	$conn->exec($log_sql);
	if(!empty($res)){
		$res = json_decode($res,true);
		if(isset($res['id'])){
			$isql = "INSERT INTO `order_details` (`email_id`, `invoice_id`, `order_id`, `bg_customer_id`, `reponse_params`, `total_inc_tax`, `total_ex_tax`, `currecy`) VALUES ('".$email_id."', '".$invoiceId."', '".$res['id']."', '".$res['customer_id']."', '".addslashes(json_encode($res))."', '".$res['total_inc_tax']."', '".$res['total_ex_tax']."', '".$res['currency_code']."')";
			$conn->exec($isql);
		}
	}
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