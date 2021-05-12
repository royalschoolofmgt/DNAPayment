<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$logger = new Logger('DNA Success Order');
$logger->pushHandler(new StreamHandler('var/logs/DNA_success.txt', Logger::INFO));
$logger->info("authKey: ".$_REQUEST['authKey']);

if(isset($_REQUEST['authKey'])){
	$tokenData = json_decode(base64_decode($_REQUEST['authKey']),true);
	sleep(3);
	$email_id = $tokenData['email_id'];
	$invoice_id = $tokenData['invoice_id'];
	$validation_id = $tokenData['key'];
	if(filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
		$conn = getConnection();
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if(isset($result[0])) {
			$invoice_stmt = $conn->prepare("select * from order_details where email_id=? and invoice_id=? and token_validation_id=?");
			$invoice_stmt->execute([$email_id,$invoice_id,$validation_id]);
			$invoice_stmt->setFetchMode(PDO::FETCH_ASSOC);
			$invoice_result = $invoice_stmt->fetchAll();
			if(isset($invoice_result[0])) {

				$logger->info("Before execution of redirectBigcommerce.");

				$result = $result[0];
				redirectBigcommerce($result,$email_id,$invoice_id,$validation_id);
			}else{
				$result = $result[0];

				$logger->info("Before execution of createBGOrder.");

				createBGOrder($invoice_id,$validation_id);
				redirectBigcommerce($result,$email_id,$invoice_id,$validation_id);
			}
		}
	}
}
function redirectBigcommerce($result,$email_id,$invoice_id,$validation_id){
	global $logger;
	$conn = getConnection();
	$acess_token = $result['acess_token'];
	$store_hash = $result['store_hash'];
	
	$header = array(
		"store_hash: ".$store_hash,
		"X-Auth-Token: ".$acess_token,
		"Accept: application/json",
		"Content-Type: application/json"
	);
	
	$url = STORE_URL.$store_hash.'/v2/store';
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res = curl_exec($ch);
	curl_close($ch);

	$logger->info("RedirectBigcommerce - Store API Response : ".$res);

	if(!empty($res)){
		$res = json_decode($res,true);
		if(isset($res['secure_url'])){
			
			$invoice_stmt = $conn->prepare("select * from order_details where email_id=? and invoice_id=? and token_validation_id=?");
			$invoice_stmt->execute([$email_id,$invoice_id,$validation_id]);
			$invoice_stmt->setFetchMode(PDO::FETCH_ASSOC);
			$invoice_result = $invoice_stmt->fetchAll();
			if(isset($invoice_result[0])) {
				$invoice_result = $invoice_result[0];
				$order_id = $invoice_result['order_id'];
				$invoice_id = $invoice_result['invoice_id'];
				$bg_customer_id = $invoice_result['bg_customer_id'];
				if($bg_customer_id > 0){

					$logger->info("Redirecting to order-confirmation.");

					//header("Location:".$res['secure_url'].'/checkout/order-confirmation/'.$order_id);die();
					$url = $res['secure_url'].'/checkout/order-confirmation/'.$order_id;
					echo '<script>window.parent.location.href="'.$url.'";</script>';
				}else{
					$logger->info("Redirecting to custom-order-confirmation.");

					$invoice_id = base64_encode(json_encode($invoice_id,true));
					//header("Location:".$res['secure_url'].'/custom-order-confirmation?authKey='.$invoice_id);die();
					$url = $res['secure_url'].'/custom-order-confirmation?authKey='.$invoice_id;
					echo '<script>window.parent.location.href="'.$url.'";</script>';
				}	
			}else{
				$logger->info("Some error creating Bigcommerce Order.");
				echo "Some error creating Bigcommerce Order";exit;
			}
		}
	}
}
function createBGOrder($invoiceId,$validation_id){
	global $logger;
	$conn = getConnection();
	$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id=? and token_validation_id=?");
	$stmt_order_payment->execute([$invoiceId,$validation_id]);
	$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
	$result_order_payment = $stmt_order_payment->fetchAll();
	if (isset($result_order_payment[0])) {
		$result_order_payment = $result_order_payment[0];
		
		$string = base64_decode($result_order_payment['params']);
		$string = preg_replace("/[\r\n]+/", " ", $string);
		$json = utf8_encode($string);
		$cartData = json_decode($json,true);
		$items_total = 0;
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute($result_order_payment['email_id'],$validation_id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		//print_r($result[0]);exit;
		if (isset($result[0])) {
			$result = $result[0];
			$acess_token = $result['acess_token'];
			$store_hash = $result['store_hash'];
			
			$order_products = array();
			foreach($cartData['cart']['line_items'] as $liv){
				$cart_products = $liv;
				foreach($cart_products as $k=>$v){
					if($v['variant_id'] > 0){
						$details = array();
						$productOptions = productOptions($acess_token,$store_hash,$result_order_payment['email_id'],$v['product_id'],$v['variant_id'],$validation_id);

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
										"shipping_method" => $cart_shipping_options['type']
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
			
			$createOrder['payment_method'] = "custom";
			$createOrder['external_source'] = "247 DNA";
			$createOrder['default_currency_code'] = $cartData['cart']['currency']['code'];
			
			$logger->info("Before update order status API call");

			$bigComemrceOrderId = createOrder($acess_token,$store_hash,$result_order_payment['email_id'],$createOrder,$invoiceId,$validation_id);

			$logger->info("Create order API response: ".$bigComemrceOrderId);

			if($bigComemrceOrderId != "") {
				//update order status for trigger status update mail from bigcommerce
				$logger->info("Before update order status API call");
				$statusResponse = updateOrderStatus($bigComemrceOrderId, $acess_token, $store_hash, $result_order_payment['email_id'],$validation_id);

				$logger->info("Update order status API response: ".$statusResponse);
			}
			
		}
	}
}
function productOptions($acess_token,$store_hash,$email_id,$productId,$variantId,$validation_id){
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
function createOrder($acess_token,$store_hash,$email_id,$request,$invoiceId,$validation_id){
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

		}else{
			echo "Some error creating Bigcommerce Order";echo "<br/>";
			echo @$res[0]['message'];
		}
	}

	return $bigComemrceOrderId;

}

function updateOrderStatus($bigComemrceOrderId,$acess_token,$store_hash,$email_id,$validation_id) {
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