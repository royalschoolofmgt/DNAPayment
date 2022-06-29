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
$logger = new Logger('Settlement');
$logger->pushHandler(new StreamHandler('var/logs/DNA_settlement_log.txt', Logger::INFO));

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
if(isset($postData['invoice_id'])){
	$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
	$sql = "select * from order_payment_details where email_id=? and order_id=?";
	$stmt_refund = $conn->prepare($sql);
	$stmt_refund->execute([$email_id,$postData['invoice_id']]);
	$stmt_refund->setFetchMode(PDO::FETCH_ASSOC);
	$result_refund = $stmt_refund->fetchAll();
	if(isset($result_refund[0]) && ($result_refund[0]['type'] == "AUTH") && ($result_refund[0]['settlement_status'] != "CHARGE")) {
		$payment_details = json_decode(str_replace("\\","",$result_refund[0]['api_response']),true);
		//print_r($payment_details);exit;
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
				$stmt->execute([$res['response']['transactionState'],$res['response']['payoutAmount'],addslashes(json_encode($res['response'])),$postData['invoice_id']]);
				$statusResponse = updateOrderStatus($email_id,$postData['invoice_id'],$validation_id);
				header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
			}else{
				$usql = "UPDATE order_payment_details set settlement_status=?,settlement_response=? where order_id=?";
				$stmt = $conn->prepare($usql);
				$stmt->execute(['FAILED',addslashes(json_encode($res['response'])),$postData['invoice_id']]);
				header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=1');
			}
		}else{
			header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}else{
	header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
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

function updateOrderStatus($email_id,$invoice_id,$validation_id) {
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
			
			if(isset($order_details['order_id']) && !empty($order_details['order_id'])){
				
				$staff_comments = previousOrderNotes($email_id,$validation_id,$order_details['order_id']);
				
				$url_u = STORE_URL.$store_hash.'/v2/orders/'.$order_details['order_id'];
				$staff_comments .= "\n\nPayment Number : ".$invoice_id.",Status : Settled,Settlement Date : ".date("Y-m-d h:i A");
				
				$request_u = array("status_id"=>2,"staff_notes"=>$staff_comments);
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

?>