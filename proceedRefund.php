<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['247authsess'])){
	header("Location:index.php");
}
require_once('config.php');
require_once('db-config.php');

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

$postData = $_REQUEST;
if(isset($postData['invoice_id']) && isset($postData['refund_amount'])){
	$sql = "select * from order_payment_details where email_id='".$email_id."' and order_id='".$postData['invoice_id']."'";
	$stmt_refund = $conn->prepare($sql);
	$stmt_refund->execute();
	$stmt_refund->setFetchMode(PDO::FETCH_ASSOC);
	$result_refund = $stmt_refund->fetchAll();
	if(isset($result_refund[0])) {
		$payment_details = json_decode($result_refund[0]['api_response'],true);
		$request = array(
						"id"=>$payment_details['id'],
						"amount"=>$postData['refund_amount']
					);
		$isql = "insert into order_refund(email_id,invoice_id,refund_status,refund_amount,api_request) values('".$email_id."','".$postData['invoice_id']."','PENDING','".$postData['refund_amount']."','".addslashes(json_encode($request))."')";
		$conn->exec($isql);
		$last_id = $conn->lastInsertId();
		//echo $last_id;exit;
		$res = processRefund($email_id,$request);
		if(isset($res['response'])){
			if(isset($res['response']['payoutAmount'])){
				$usql = "UPDATE order_refund set refund_status='".$res['response']['state']."',api_request='".addslashes($res['response'])."' where r_id=".$last_id;
				$conn->prepare($isql);
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
			}else{
				$usql = "UPDATE order_refund set refund_status='FAILED',api_request='".addslashes($res['response'])."' where r_id=".$last_id;
				$conn->prepare($isql);
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=1');
			}
		}else{
			header("Location:refundOrder.php?bc_email_id=".@$_REQUEST['bc_email_id']."&auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
		}
	}else{
		header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}else{
	header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
}

function processRefund($email_id,$request){
	$conn = getConnection();
	$data = array();
	//$bearer_token = authorization($email_id);
	
	//if(!empty($bearer_token)){
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json",
			//"Authorization: Bearer ".$bearer_token
		);

		//print_r($request);exit;
		$url = REFUND_URL;
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
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","DNA","Refund Process","'.addslashes($url).'","'.addslashes(json_encode($request)).'","'.addslashes($res).'")';
		//echo $log_sql;exit;
		$conn->exec($log_sql);
		
		$data['request'] = $request;
		if(!empty($res)){
			$data = json_decode($res,true);
			if(isset($data['success'])){
				$data['response'] = $data;
			}
		}
	//}
	
	return $data;
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
						"Scope"=>"webapi",
						"client_id"=>$result['client_id'],
						"client_secret"=>$result['client_secret'],
						"grant_type"=>"client_credentials",
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