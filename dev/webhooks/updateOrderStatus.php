<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
/**
	* Webhook
	* Author 247Commerce
	* Date 11 MAR 2021
*/
/*
Webhook for Order Status Update --> Bigcommerce
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once("../db-config.php");
require_once("../config.php");
require_once("../helper.php");

require '../log-autoloader.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


if(isset($_REQUEST['bc_email_id']) && isset($_REQUEST['key'])){
	
	$data = file_get_contents('php://input');
	$fp = fopen("webhook.txt", "w");
	   fwrite($fp, serialize($data));
	   fclose($fp);
	if(!empty($data)){
		$check_errors = json_decode($data);
		if(isset($check_errors->errors)){
		}else{
			if(json_last_error() === 0){
				$data = json_decode($data,true);
				$order_data = $data['data'];
				if(isset($order_data['id']) && isset($order_data['status']) && isset($order_data['status']['new_status_id'])){
					$order_id = $order_data['id'];
					$conn = getConnection();
					$email_id = $_REQUEST['bc_email_id'];
					$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
					$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
					$stmt->execute([$email_id,$validation_id]);
					$stmt->setFetchMode(PDO::FETCH_ASSOC);
					$result = $stmt->fetchAll();
					if(isset($result[0])){
						$result = $result[0];
						$acess_token = $result['acess_token'];
						$store_hash = $result['store_hash'];
						
						$stmt_order_det = $conn->prepare("select * from order_details where order_id=?");
						$stmt_order_det->execute([$order_id]);
						$stmt_order_det->setFetchMode(PDO::FETCH_ASSOC);
						$result_order_det = $stmt_order_det->fetchAll();
						if(isset($result_order_det[0])){
							$result_order_det = $result_order_det[0];
							//$data = getOrderData($order_id,$acess_token,$store_hash);
							//if($data['status']){
								if($order_data['status']['new_status_id'] == "2"){
									orderSettlement($email_id,$result_order_det['invoice_id'],$validation_id);
								}
							//}
						}
					}
				}
			}
		}
		
	}
}

function getOrderData($orderId,$store_hash,$acess_token){
	$res = array();
	$res['status'] = false;
	$conn = getConnection();
	$res['status_id'] = '';
	$url_T = STORE_URL.$store_hash.'/v2/orders/'.$orderId;
	
	$httpheaders_T[]="Accept:application/json";
	$httpheaders_T[]="Content-Type:application/json";
	$httpheaders_T[]="X-Auth-Token:".$acess_token;
	$httpheaders_T[]="X-Auth-Client:".$acess_token;
	$result_T = '';
	$ch_T = curl_init($url_T);
	curl_setopt($ch_T, CURLOPT_HTTPHEADER, $httpheaders_T); // send my headers
	curl_setopt($ch_T, CURLOPT_RETURNTRANSFER, true); // return result in a variable
	curl_setopt($ch_T, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch_T, CURLOPT_FOLLOWLOCATION, true);                    

	$result_T = curl_exec($ch_T);
	//print_r($result_T);exit;
	$check_errors = json_decode($result_T);
	if(isset($check_errors->errors)){
	}else{
		if(json_last_error() === 0){
			$response = json_decode($result_T,true);
			if(isset($response['status_id'])){
				$res['status_id'] = $response['status_id'];
			}
		}
	}
	return $res;
}
function orderSettlement($email_id,$invoice_id,$validation_id){
	
	// create a log channel
	$logger = new Logger('Settlement');
	$logger->pushHandler(new StreamHandler('var/logs/DNA_settlement_log.txt', Logger::INFO));

	$conn = getConnection();
	$sql = "select * from order_payment_details where email_id=? and order_id=?";
	$stmt_refund = $conn->prepare($sql);
	$stmt_refund->execute([$email_id,$invoice_id]);
	$stmt_refund->setFetchMode(PDO::FETCH_ASSOC);
	$result_refund = $stmt_refund->fetchAll();
	if(isset($result_refund[0]) && ($result_refund[0]['type'] == "AUTH") && ($result_refund[0]['settlement_status'] != "CHARGE")) {
		$payment_details = json_decode(str_replace("\\","",$result_refund[0]['api_response']),true);
		$request = array(
						"id"=>$payment_details['id'],
						"amount"=>(float)$result_refund[0]['total_amount']
					);
		
		$logger->info("Before processSettlement API call");
		$res = processSettlement($email_id,$request,$validation_id);
		$logger->info("Settlement API response: ".json_encode($res));

		if(isset($res['response'])){
			if(isset($res['response']['payoutAmount'])){
				$usql = "UPDATE order_payment_details set settlement_status=?,amount_paid=?,settlement_response=? where order_id=?";
				$stmt = $conn->prepare($usql);
				$stmt->execute([$res['response']['transactionState'],$res['response']['payoutAmount'],addslashes(json_encode($res['response'])),$invoice_id]);
			}else{
				$usql = "UPDATE order_payment_details set settlement_status=?,settlement_response=? where order_id=?";
				$stmt = $conn->prepare($usql);
				$stmt->execute(['FAILED',addslashes(json_encode($res['response'])),$invoice_id]);
			}
		}
	}
}
function processSettlement($email_id,$request,$validation_id){
	$conn = getConnection();
	$data = array();
	$bearer_token = authorization($email_id,$validation_id);
	
	if(!empty($bearer_token)){
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		//print_r($result[0]);exit;
		if (isset($result[0])) {
			$result = $result[0];
			$is_test_live = $result['is_test_live'];
			$header = array(
				"Accept: application/json",
				"Content-Type: application/json",
				"Authorization: Bearer ".$bearer_token
			);
			
			$request = json_encode($request);
			
			if($is_test_live == '0'){
				$url = SETTLE_URL_TEST;
			}else{
				$url = SETTLE_URL;
			}
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);   
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			
			$res = curl_exec($ch);
			curl_close($ch);
			
			$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
			
			$stmt = $conn->prepare($log_sql);
			$stmt->execute([$email_id,"DNA","Settlement Process",addslashes($url),addslashes($request),addslashes($res),$validation_id]);
			
			////$data['request'] = $request;
			$data['response'] = array();
			if(!empty($res)){
				$res = json_decode($res,true);
				if(isset($res['success'])){
					$data['response'] = $res;
				}
			}
		}
	}
	
	return $data;
}

function authorization($email_id,$validation_id){
	$bearer_token = '';
	
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		$is_test_live = $result['is_test_live'];
		if($is_test_live == '1'){
			$client_id = $result['client_id'];
			$client_secret = $result['client_secret'];
			$client_terminal_id = $result['client_terminal_id'];
		}else{
			$client_id = $result['client_id_test'];
			$client_secret = $result['client_secret_test'];
			$client_terminal_id = $result['client_terminal_id_test'];
		}
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
						"scope"=>"webapi",
						"client_id"=>$client_id,
						"client_secret"=>$client_secret,
						"grant_type"=>"client_credentials",
					);
		
		if($is_test_live == '0'){
			$url = AUTHENTICATE_URL_TEST;
		}else{
			$url = AUTHENTICATE_URL;
		}
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
		$stmt->execute([$email_id,"DNA","Authentication",addslashes($url),addslashes($request),addslashes($res),$validation_id]);
		
		if(!empty($res)){
			$data = json_decode($res,true);
			if(isset($data['access_token'])){
				$bearer_token = $data['access_token'];
			}
		}
		return $bearer_token;
	}
}

//status ids
/*"1"=>Pending
"2"=>Shipped
"3"=>Partially Shipped
"4" selected="true"=>Refunded
"5"=>Cancelled
"6"=>Declined
"7"=>Awaiting Payment
"8"=>Awaiting Pickup
"9"=>Awaiting Shipment
"10"=>Completed
"11"=>Awaiting Fulfillment
"12"=>Manual Verification Required
"13"=>Disputed
"14"=>Partially Refunded*/

?>