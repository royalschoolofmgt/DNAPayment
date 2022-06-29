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

$postData = $_REQUEST;
if(isset($postData['invoice_id']) && isset($postData['refund_amount'])){
	$conn = getConnection();
	$sql = "select * from order_payment_details where order_id='".$postData['invoice_id']."'";
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
		$isql = "insert into order_refund(invoice_id,refund_status,refund_amount,api_request) values('".$postData['invoice_id']."','PENDING','".$postData['refund_amount']."','".addslashes(json_encode($request))."')";
		$conn->exec($isql);
		$last_id = $conn->lastInsertId();
		//echo $last_id;exit;
		$res = processRefund($result_refund[0]['email_id'],$request);
		if(isset($res['response'])){
			if(isset($res['response']['payoutAmount'])){
				$usql = "UPDATE order_refund set refund_status='".$res['response']['state']."',api_request='".addslashes($res['response'])."' where r_id=".$last_id;
				$conn->prepare($isql);
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				header("Location:refundOrder.php?auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=0');
			}else{
				$usql = "UPDATE order_refund set refund_status='FAILED',api_request='".addslashes($res['response'])."' where r_id=".$last_id;
				$conn->prepare($isql);
				$stmt = $conn->prepare($usql);
				$stmt->execute();
				header("Location:refundOrder.php?auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=1');
			}
		}else{
			header("Location:refundOrder.php?auth=".base64_encode(json_encode($postData['invoice_id'])).'&error=2');
		}
	}else{
		header("Location:dashboard.php");
	}
}else{
	header("Location:dashboard.php");
}

function processRefund($email_id,$request){
	$conn = getConnection();
	$header = array(
		"Accept: application/json",
		"Content-Type: application/json"
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
	
	$data = array();
	$data['request'] = $request;
	if(!empty($res)){
		$data = json_decode($res,true);
		if(isset($data['success'])){
			$data['response'] = $data;
		}
	}
	
	return $data;
}

?>