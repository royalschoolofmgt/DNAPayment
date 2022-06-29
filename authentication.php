<?php
/**
	* Token Validation Page
	* Author 247Commerce
	* Date 30 SEP 2020
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Credentials: true');
}

require_once('config.php');
require_once('db-config.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$res = array();
$res['status'] = false;
$res['data'] = '';
$res['msg'] = '';

$logger = new Logger('Authentication');
$logger->pushHandler(new StreamHandler('var/logs/DNA_auth_log.txt', Logger::INFO));
$logger->info("authKey: ".$_REQUEST['authKey']);
//$logger->info("cartData: ".$_REQUEST['cartData']);

if(isset($_REQUEST['authKey'])){
	$valid = validateAuthentication($_REQUEST);
	if($valid){
		$tokenData = json_decode(base64_decode($_REQUEST['authKey']),true);
		$email_id = $tokenData['email_id'];
		$validation_id = $tokenData['key'];
		if (filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
			$conn = getConnection();
			$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$result = $stmt->fetchAll();
			//print_r($result[0]);exit;
			if (isset($result[0])) {
				$result = $result[0];
				$payment_option = $result['payment_option'];
				if(!empty($result['client_id']) && !empty($result['client_secret']) && !empty($result['client_terminal_id'])){
					$sellerdb = $result['sellerdb'];
					$acess_token = $result['acess_token'];
					$store_hash = $result['store_hash'];
					
					$cartAPIRes = getCartData($email_id,$_REQUEST['cartId'],$acess_token,$store_hash,$validation_id);
					if(!is_array($cartAPIRes) || (is_array($cartAPIRes) && count($cartAPIRes) == 0)) {

						$res['status'] = false;
						echo json_encode($res);
						exit;
					}

					//to use cart data from server API response to avoid manipulation from UI side
					$cartData = $cartAPIRes;					
					
					/*$string = base64_decode($_REQUEST['cartData']);
					$string = preg_replace("/[\r\n]+/", " ", $string);
					$json = utf8_encode($string);
					$cartData = json_decode($json,true);*/
					
					if(!empty($cartData) && isset($cartData['id'])){
						$totalAmount = $cartData['grand_total'];
						$transaction_type = "AUTH";
						if($payment_option == "CFO"){
							$transaction_type = "SALE";
							$totalAmount = $cartData['grand_total'];
						}
						$currency = $cartData['cart']['currency']['code'];
						$billingAddress = $cartData['billing_address'];
						//$invoiceId = "DNA".time();
						$invoiceId = "DNA-".$result['validation_id'].'-'.uniqid().'-'.time();
						$request = array(
							"scope" => "payment integration_embedded",
							"client_id" => $result['client_id'],
							"client_secret" => $result['client_secret'],
							"grant_type" => "client_credentials",
							"invoiceId" => $invoiceId,
							"amount" => $totalAmount,
							"currency" => $currency,
							"terminal" => $result['client_terminal_id']
						);

						$logger->info("Before Auth Token API call.");

						$api_response = oauth2_token($email_id,$request,$validation_id);
						
						$logger->info("After Auth Token API Response.");

						if(isset($api_response['response'])){
							$isql = 'insert into order_payment_details(type,email_id,order_id,cart_id,total_amount,amount_paid,currency,status,params,token_validation_id) values(?,?,?,?,?,?,?,?,?,?)';
							$stmt= $conn->prepare($isql);
							$stmt->execute([$transaction_type, $email_id, $invoiceId,$cartData['id'],$cartData['grand_total'],"0.00",$currency,"PENDING",base64_encode(json_encode($cartData)),$validation_id]);
							$res['status'] = true;
							$tokenData = array("email_id"=>$email_id,"key"=>$validation_id,"invoice_id"=>$invoiceId);
							$data = array(
										"invoiceId" => $invoiceId,
										"backLink" => BASE_URL."success.php?authKey=".base64_encode(json_encode($tokenData)),
										"failureBackLink" => BASE_URL."failure.php?authKey=".base64_encode(json_encode($tokenData)),
										"postLink" => BASE_URL."updateOrder.php",
										"failurePostLink" => BASE_URL."updateFailedOrder.php",
										"language" => "EN",
										"description" => "Order payment",
										"accountId" => "testuser",
										"phone" => $billingAddress['phone'],
										"transactionType" => $transaction_type,
										"terminal" => $result['client_terminal_id'],
										"amount" => $totalAmount,
										"currency" => $currency,
										"accountCountry" => $billingAddress['country_code'],
										"accountCity" => $billingAddress['country'],
										"accountStreet1" => $billingAddress['address1'],
										"accountEmail" => $billingAddress['email'],
										"accountFirstName" => $billingAddress['first_name'],
										"accountLastName" => $billingAddress['last_name'],
										"accountPostalCode" => $billingAddress['postal_code'],
										"auth" => $api_response['response']
									);
							$res['data'] = base64_encode(json_encode($data));
							
						}else{
							$res['msg'] = 'Something went wrong! Please check the data or try again later.';
						}
					}
				}
			}
		}
	}
}
echo json_encode($res);exit;

function validateAuthentication($request){
	$valid = true;
	if(isset($request['authKey'])){
		
	}else{
		$valid = false;
	}
	if(isset($request['cartId'])){
		
	}else{
		$valid = false;
	}
	return $valid;
}
function oauth2_token($email_id,$request,$validation_id){
	$conn = getConnection();
	$header = array(
		"Accept: application/json",
		"Content-Type: application/json"
	);

	//print_r($request);exit;
	$url = AUTHENTICATE_URL;
	$ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);   
    curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	
	$res = curl_exec($ch);
	curl_close($ch);

	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
	$stmt = $conn->prepare($log_sql);
	$stmt->execute([$email_id,"DNA","Authentication",addslashes($url),addslashes(json_encode($request,true)),addslashes($res),$validation_id]);
	
	$data = array();
	$data['request'] = $request;
	if(!empty($res)){
		$data = json_decode($res,true);
		if(isset($data['access_token'])){
			$data['response'] = $data;
		}
	}
	
	return $data;
}

function getCartData($email_id,$cartId,$acess_token,$store_hash,$validation_id){
	$data = array();
	if(!empty($cartId) && !empty($email_id)){
		$conn = getConnection();
		$header = array(
				"store_hash: ".$store_hash,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
		$request = '';
		$url = STORE_URL.$store_hash.'/v3/checkouts/'.$cartId.'?include=cart.line_items.physical_items.options%2Ccart.line_items.digital_items.options%2Ccustomer%2Ccustomer.customerGroup%2Cpayments%2Cpromotions.banners%2Ccart.line_items.physical_items.categoryNames%2Ccart.line_items.digital_items.category_names';
		//print_r($url);exit;
		$ch = curl_init($url); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		//curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		//curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		//print_r($res);exit;
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
		$stmt = $conn->prepare($log_sql);
		$stmt->execute([$email_id,"BigCommerce","Checkout",addslashes($url),addslashes($request),addslashes($res),$validation_id]);
		
		if(!empty($res)){
			$res = json_decode($res,true);
			if(isset($res['data'])){
				$data = $res['data'];
			}
		}
	}
	
	return $data;
}
?>