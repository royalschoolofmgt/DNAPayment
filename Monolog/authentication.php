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

$res = array();
$res['status'] = false;
$res['data'] = '';
$res['msg'] = '';

if(isset($_REQUEST['authKey'])){
	$valid = validateAuthentication($_REQUEST);
	if($valid){
		$email_id = json_decode(base64_decode($_REQUEST['authKey']));
		if (filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
			$conn = getConnection();
			$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
			$stmt->execute();
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
					//$cartData = getCartData($email_id,$_REQUEST['cartId'],$acess_token,$store_hash);
					$string = base64_decode($_REQUEST['cartData']);
					$string = preg_replace("/[\r\n]+/", " ", $string);
					$json = utf8_encode($string);
					$cartData = json_decode($json,true);
					if(!empty($cartData) && isset($cartData['id'])){
						$totalAmount = $cartData['grandTotal'];
						$transaction_type = "AUTH";
						if($payment_option == "CFO"){
							$transaction_type = "SALE";
							$totalAmount = $cartData['grandTotal'];
						}
						$currency = $cartData['cart']['currency']['code'];
						$billingAddress = $cartData['billingAddress'];
						$invoiceId = "247dna_".time();
						$request = array(
							"scope" => "payment integration_seamless",
							"client_id" => $result['client_id'],
							"client_secret" => $result['client_secret'],
							"grant_type" => "client_credentials",
							"invoiceId" => $invoiceId,
							"amount" => $totalAmount,
							"currency" => $currency,
							"terminal" => $result['client_terminal_id']
						);
						$api_response = oauth2_token($email_id,$request);
						//print_r($api_response);exit;
						if(isset($api_response['response'])){
							$isql = 'insert into order_payment_details(type,email_id,order_id,cart_id,total_amount,amount_paid,currency,status,params) values("'.$transaction_type.'","'.$email_id.'","'.$invoiceId.'","'.$cartData['id'].'","'.$cartData['grandTotal'].'","'.$totalAmount.'","'.$currency.'","PENDING","'.$_REQUEST['cartData'].'")';
							$conn->exec($isql);
							$res['status'] = true;
							$tokenData = array("email_id"=>$email_id,"invoice_id"=>$invoiceId);
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
										"accountCountry" => $billingAddress['countryCode'],
										"accountCity" => $billingAddress['country'],
										"accountStreet1" => $billingAddress['address1'],
										"accountEmail" => $billingAddress['email'],
										"accountFirstName" => $billingAddress['firstName'],
										"accountLastName" => $billingAddress['lastName'],
										"accountPostalCode" => $billingAddress['postalCode'],
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
	if(isset($request['cartData'])){
		
	}else{
		$valid = false;
	}
	return $valid;
}
function oauth2_token($email_id,$request){
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
	//print_r($res);exit;
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","DNA","Authentication","'.addslashes($url).'","'.addslashes(json_encode($request)).'","'.addslashes($res).'")';
	//echo $log_sql;exit;
	$conn->exec($log_sql);
	
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

function getCartData($email_id,$cartId,$acess_token,$store_hash){
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
		$url = STORE_URL.$store_hash.'/v3/carts/'.$cartId;
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
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","cart","'.addslashes($url).'","'.addslashes(json_encode($request)).'","'.addslashes($res).'")';
		//echo $log_sql;exit;
		$conn->exec($log_sql);
		
		if(!empty($res)){
			$res = json_decode($res,true);
			if(isset($res['data'])){
				$data['response'] = $res['data'];
			}
		}
	}
	
	return $data;
}
?>