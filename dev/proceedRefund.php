<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}

require_once('config.php');
require_once('db-config.php');
require_once('store_helper.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$logger = new Logger('Refund');
$logger->pushHandler(new StreamHandler('var/logs/DNA_refund_log.txt', Logger::INFO));

$conn = getConnection();
$email_id = '';

if(isset($_REQUEST['bc_email_id']) && isset($_REQUEST['key'])){
	$email_id = $_REQUEST['bc_email_id'];
	$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		if($result['is_test_live'] == '1'){
			if(empty($result['client_id']) && empty($result['client_secret']) && empty($result['client_terminal_id'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}else{
			if(empty($result['client_id_test']) && empty($result['client_secret_test']) && empty($result['client_terminal_id_test'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}

$postData = $_REQUEST;
$logger->info("Invoice ID: ".$postData['invoice_id']);
$logger->info("Refund Amount: ".$postData['refund_amount']);
if(isset($postData['invoice_id']) && isset($postData['refund_amount'])){
	$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
	$sql = "select * from order_payment_details where email_id=? and order_id=?";
	$stmt_refund = $conn->prepare($sql);
	$stmt_refund->execute([$email_id,$postData['invoice_id']]);
	$stmt_refund->setFetchMode(PDO::FETCH_ASSOC);
	$result_refund = $stmt_refund->fetchAll();
	$refund_option = $postData['refund_option'];
	if(isset($result_refund[0])) {
		$payment_details = json_decode(str_replace("\\","",$result_refund[0]['api_response']),true);
		$params = json_decode(str_replace("\\","",base64_decode($result_refund[0]['params'])),true);
		$request = array(
						"id"=>$payment_details['id'],
						"amount"=>(float)$postData['refund_amount']
					);
		$isql = "insert into order_refund(email_id,invoice_id,refund_status,refund_amount,api_request,token_validation_id) values(?,?,?,?,?,?)";
		$stmt_isql = $conn->prepare($isql);
		$stmt_isql->execute([$email_id,$postData['invoice_id'],'PENDING',$postData['refund_amount'],addslashes(json_encode($request)),$validation_id]);
		$last_id = $conn->lastInsertId();
		$refund_type = 'RC';
		if($refund_option == 1 && isset($params['cart']['customer_id']) && $params['cart']['customer_id'] > 0){
			$refund_type = 'RS';
			$response = updateCustomerStoreCredit($params['cart']['customer_id'],$postData['refund_amount'],$email_id,$validation_id);
			if($response['status']){
				$usql = "UPDATE order_refund set refund_status=?,api_response=? where r_id=?";
				$stmt = $conn->prepare($usql);
				$stmt->execute(['REFUND',addslashes(json_encode($response['data'])),$last_id]);
				
				$usql_p = "UPDATE order_payment_details set settlement_status=?,refund_type=? where order_id=?";
				$stmt_p = $conn->prepare($usql_p);
				$stmt_p->execute(['REFUND',$refund_type,$postData['invoice_id']]);
				
				$statusResponse = updateOrderStatus($email_id,$last_id,$postData['invoice_id'],$validation_id);
					
				header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
			}else{
				header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
			}
		}else{
			$logger->info("Before processRefund API call");
			$res = processRefund($email_id,$request,$validation_id);
			$logger->info("Refund API response: ".json_encode($res));

			if(isset($res['response'])){
				if(isset($res['response']['payoutAmount'])){
					$usql = "UPDATE order_refund set refund_status=?,api_response=? where r_id=?";
					$stmt = $conn->prepare($usql);
					$stmt->execute([$res['response']['transactionState'],addslashes(json_encode($res['response'])),$last_id]);
					
					$usql_p = "UPDATE order_payment_details set settlement_status=?,refund_type=? where order_id=?";
					$stmt_p = $conn->prepare($usql_p);
					$stmt_p->execute([$res['response']['transactionState'],$refund_type,$postData['invoice_id']]);
					
					$statusResponse = updateOrderStatus($email_id,$last_id,$postData['invoice_id'],$validation_id);
					
					header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
				}else{
					$usql = "UPDATE order_refund set refund_status=?,api_response=? where r_id=?";
					$conn->prepare($isql);
					$stmt = $conn->prepare($usql);
					$stmt->execute(['FAILED',addslashes(json_encode($res['response'])),$last_id]);
					header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=1');
				}
			}else{
				header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
			}
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}else{
	header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
}

function processRefund($email_id,$request,$validation_id){
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
				$url = REFUND_URL_TEST;
			}else{
				$url = REFUND_URL;
			}
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			$res = curl_exec($ch);
			curl_close($ch);
			
			$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
			
			$stmt = $conn->prepare($log_sql);
			$stmt->execute([$email_id,"DNA","Refund Process",addslashes($url),addslashes($request),addslashes($res),$validation_id]);
			
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

function updateOrderStatus($email_id,$rder_refund_id,$invoice_id,$validation_id) {
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		//if(!empty($result['client_id']) || !empty($result['client_secret']) || !empty($result['client_terminal_id'])){
			$acess_token = $result['acess_token'];
			$store_hash = $result['store_hash'];
			
			$order_details = array();
			$stmt_od = $conn->prepare("select * from order_details where invoice_id=?");
			$stmt_od->execute([$invoice_id]);
			$stmt_od->setFetchMode(PDO::FETCH_ASSOC);
			$result_od = $stmt_od->fetchAll();
			if (isset($result_od[0])) {
				$order_details = $result_od[0];
			}
			
			$order_refund_details = array();
			$stmt_or = $conn->prepare("select * from order_refund where r_id=?");
			$stmt_or->execute([$rder_refund_id]);
			$stmt_or->setFetchMode(PDO::FETCH_ASSOC);
			$result_or = $stmt_or->fetchAll();
			if (isset($result_or[0])) {
				$order_refund_details = $result_or[0];
			}
			
			if(isset($order_details['order_id']) && !empty($order_details['order_id']) && isset($order_refund_details['refund_status']) && ($order_refund_details['refund_status'] == "REFUND")){
				
				$staff_comments = previousOrderNotes($email_id,$validation_id,$order_details['order_id']);
				
				$url_u = STORE_URL.$store_hash.'/v2/orders/'.$order_details['order_id'];
				
				$staff_comments .= "\n\nPayment Number : ".$invoice_id.",Status : Refunded,Refunded Date : ".$order_refund_details['created_date'].",Refunded Amount : ".$order_details['currecy']." ".$order_refund_details['refund_amount'];
				
				$request_u = array("status_id"=>4,"staff_notes"=>$staff_comments);
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
				
				$stmt = $conn->prepare($log_sql);
				$stmt->execute([$email_id,"BigCommerce","Update Order",addslashes($url_u),addslashes($request_u),addslashes($res_u),$validation_id]);
				
				$u_sql = "update order_refund set order_comments=? where r_id=?";
				$stmt = $conn->prepare($u_sql);
				$stmt->execute([$staff_comments,$rder_refund_id]);
			}
		//}
	}
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
		$stmt->execute([$email_id,"DNA","Authentication",addslashes($url),addslashes(json_encode($request,true)),addslashes($res),$validation_id]);		
		
		if(!empty($res)){
			$data = json_decode($res,true);
			if(isset($data['access_token'])){
				$bearer_token = $data['access_token'];
			}
		}
		return $bearer_token;
	}
}

function updateCustomerStoreCredit($customer_id,$amount,$email_id,$validation_id) {
	$resp = array();
	$resp['status'] = false;
	$resp['msg'] = 'Error';
	$resp['data'] = array();
	
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		//if(!empty($result['client_id']) || !empty($result['client_secret']) || !empty($result['client_terminal_id'])){
			$acess_token = $result['acess_token'];
			$store_hash = $result['store_hash'];
			$url_u = STORE_URL.$store_hash.'/v3/customers';
			$header = array(
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
			$customerData = getBCCustomerData($email_id, $validation_id, $customer_id);
			if(!empty($customerData) && isset($customerData['store_credit_amounts']) && isset($customerData['store_credit_amounts'][0]['amount'])){
				$storeCreditAmount = $customerData['store_credit_amounts'][0]['amount'];
				$totalAmount = $storeCreditAmount+$amount;
				$request_u = [];
				$request_u[] = [
					'id'=>$customer_id,
					'store_credit_amounts'=>[
							[
								'amount'=>$totalAmount
							]
					]
				];
				$hsonRequest = json_encode($request_u,true);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url_u);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $hsonRequest);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$res_u = curl_exec($ch);
				curl_close($ch);
				
				$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
				$stmt = $conn->prepare($log_sql);
				$stmt->execute([$email_id,"BigCommerce","Update Customer Store Credit",addslashes($url_u),addslashes($request_u),addslashes($res_u),$validation_id]);
				if(!empty($res_u)){
					$res = json_decode($res_u,true);
					if(isset($res['data'][0]['id'])){
						$resp['status'] = true;
						$resp['data'] = $res['data'][0];
					}
				}
			}
		//}
	}
	return $resp;
}

?>