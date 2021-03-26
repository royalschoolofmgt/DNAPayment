<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}

require_once('config.php');
require_once('db-config.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$logger = new Logger('Settlement');
$logger->pushHandler(new StreamHandler('var/logs/DNA_settlement_log.txt', Logger::INFO));

$conn = getConnection();
$email_id = '';
if(isset($_REQUEST['bc_email_id'])){
	$email_id = $_REQUEST['bc_email_id'];
	$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		if(empty($result['client_id']) || empty($result['client_secret']) || empty($result['client_terminal_id'])){
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}

$logger->info("Invoice ID: ".$postData['invoice_id']);

$postData = $_REQUEST;
if(isset($postData['invoice_id'])){
	$sql = "select * from order_payment_details where email_id='".$email_id."' and order_id='".$postData['invoice_id']."'";
	$stmt_refund = $conn->prepare($sql);
	$stmt_refund->execute();
	$stmt_refund->setFetchMode(PDO::FETCH_ASSOC);
	$result_refund = $stmt_refund->fetchAll();
	if(isset($result_refund[0]) && ($result_refund[0]['type'] == "AUTH") && ($result_refund[0]['settlement_status'] != "CHARGE")) {
		$payment_details = json_decode($result_refund[0]['api_response'],true);
		$request = array(
						"id"=>$payment_details['id'],
						"amount"=>(float)$result_refund[0]['total_amount']
						////"amount"=>49.99
					);
		
		$logger->info("Before processSettlement API call");
		$res = processSettlement($email_id,$request);
		$logger->info("Settlement API response: ".json_encode($res));

		if(isset($res['response'])){
			if(isset($res['response']['payoutAmount'])){
				$usql = "UPDATE order_payment_details set settlement_status='".$res['response']['transactionState']."',amount_paid='".$res['response']['payoutAmount']."',settlement_response='".addslashes(json_encode($res['response']))."' where order_id='".$postData['invoice_id']."'";
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				$statusResponse = updateOrderStatus($email_id,$postData['invoice_id']);
				header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
			}else{
				$usql = "UPDATE order_payment_details set settlement_status='FAILED',settlement_response='".addslashes(json_encode($res['response']))."' where order_id='".$postData['invoice_id']."'";
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=1');
			}
		}else{
			header("Location:settleOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
		}
	}else{
		header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}else{
	header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
}

function processSettlement($email_id,$request){
	$conn = getConnection();
	$data = array();
	$bearer_token = authorization($email_id);
	
	if(!empty($bearer_token)){
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json",
			"Authorization: Bearer ".$bearer_token
		);
		
		$request = json_encode($request);
		
		$url = SETTLE_URL;
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
		
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","DNA","Settlement Process","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
		
		$conn->exec($log_sql);
		
		////$data['request'] = $request;
		$data['response'] = array();
		if(!empty($res)){
			$res = json_decode($res,true);
			if(isset($res['success'])){
				$data['response'] = $res;
			}
		}
	}
	
	return $data;
}

function updateOrderStatus($email_id,$invoice_id) {
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		if(!empty($result['client_id']) || !empty($result['client_secret']) || !empty($result['client_terminal_id'])){
			$acess_token = $result['acess_token'];
			$store_hash = $result['store_hash'];
			
			$order_details = array();
			$stmt_od = $conn->prepare("select * from order_details where invoice_id='".$invoice_id."'");
			$stmt_od->execute();
			$stmt_od->setFetchMode(PDO::FETCH_ASSOC);
			$result_od = $stmt_od->fetchAll();
			if (isset($result_od[0])) {
				$order_details = $result_od[0];
			}
			
			if(isset($order_details['order_id']) && !empty($order_details['order_id'])){
				$url_u = STORE_URL.$store_hash.'/v2/orders/'.$order_details['order_id'];
				$staff_comments = "Payment Number : ".$invoice_id.",Status : Settled,Settlement Date : ".date("Y-m-d h:i A");
				
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
				
				$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Update Order","'.addslashes($url_u).'","'.addslashes($request_u).'","'.addslashes($res_u).'")';
				
				$conn->exec($log_sql);
				
				$u_sql = "update order_refund set order_comments='".$staff_comments."' where r_id='".$rder_refund_id."'";
				$conn->exec($u_sql);
			}
		}
	}
}

function authorization($email_id){
	$bearer_token = '';
	
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
						"scope"=>"webapi",
						"client_id"=>$result['client_id'],
						"client_secret"=>$result['client_secret'],
						"grant_type"=>"client_credentials",
					);
		
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
		
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","DNA","Authentication","'.addslashes($url).'","'.addslashes(json_encode($request)).'","'.addslashes($res).'")';
		
		$conn->exec($log_sql);
		
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