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
require_once('store_helper.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$res = array();
$res['status'] = false;
$res['data'] = '';
$res['card_token'] = false;
$res['card_token_data'] = array();
$res['msg'] = '';

$logger = new Logger('Authentication');
$logger->pushHandler(new StreamHandler('var/logs/DNA_save_card_log.txt', Logger::INFO));
$logger->info("authKey: ".$_REQUEST['authKey']);

if(isset($_REQUEST['authKey'])) {

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
			if (isset($result[0])) {
				$result = $result[0];				
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
				
				if(!empty($cartData) && isset($cartData['id'])) {
					$billingAddress = $cartData['billing_address'];	
					$res['status'] = true;
						
					$stmt_card_token = $conn->prepare("select * from card_token where email_id=? and token_validation_id=? and customer_id=? and customer_email_id=?");
				
					$stmt_card_token->execute([$email_id,$validation_id,$cartData['cart']['customer_id'],$billingAddress['email']]);
					$stmt_card_token->setFetchMode(PDO::FETCH_ASSOC);
					$result_card_token = $stmt_card_token->fetchAll();
					if (isset($result_card_token[0])) {
						$card_token_data = array();
						$card_selection_list = array();
						foreach($result_card_token as $k=>$v){
							$temp = array();
							$temp['merchantTokenId'] = $v['merchant_card_token'];
							$temp['useStoredBillingData'] = 'false';
							$temp['cardName'] = $v['merchant_card_name'];
							$temp['isCSCRequired'] = 'false';
							$temp['cardSchemeId'] = $v['merchant_card_schema_id'];
							$temp['panStar'] = $v['merchant_card_pan'];
							$temp['expiryDate'] = $v['merchant_card_expiry'];
							$card_token_data[] = $temp;

							$card_selection_list[] = array(
								'id' => $v['id'],
								'card_name' => $v['merchant_card_name'],
								'card_pan' => $v['merchant_card_pan'],
								'card_scheme' => $v['merchant_card_schema'],
								'card_expiry' => $v['merchant_card_expiry'],
							);
						}

						$res['card_token'] = true;
						$res['card_token_data'] = $card_token_data;
						$res['card_selection_list'] = $card_selection_list;
					
					} //end of if (isset($result_card_token[0]))

				
				} //end of if(!empty($cartData) && isset($cartData['id']))
			
			} //end of if (isset($result[0]))
		
		} //end of if (filter_var($email_id, FILTER_VALIDATE_EMAIL))
	
	} //end of if($valid)

} //end of if(isset($_REQUEST['authKey']))

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