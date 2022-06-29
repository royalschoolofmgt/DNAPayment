<?php

require_once('config.php');
require_once('db-config.php');


function getCustomerStoreAmount($email_id,$validation_id,$customer_id,$total_amount){
    $customerData = getBCCustomerData($email_id, $validation_id, $customer_id);
    if (empty($customerData)) {
        return $total_amount;
    }
    if (! isset($customerData['store_credit_amounts'], $customerData['store_credit_amounts'][0]['amount'])) {
        return $total_amount;
    }
    $StoreCreditAmount = $customerData['store_credit_amounts'][0]['amount'];
    if ($StoreCreditAmount == 0) {
        return $total_amount;
    }
    if ($total_amount > $StoreCreditAmount) {
        return ($total_amount - $StoreCreditAmount);
    } else {
        return 0;
    }
}

function getBCCustomerData($email_id,$validation_id,$customer_id){
    $data = [];
    if (empty($customer_id) && empty($email_id) && empty($validation_id)) {
        return $data;
    }
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		$sellerdb = $result['sellerdb'];
		$acess_token = $result['acess_token'];
		$store_hash = $result['store_hash'];
		$header = array(
					"store_hash: ".$store_hash,
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
		$request = '';
		$url = STORE_URL.$store_hash.'/v3/customers?id:in=' . $customer_id . '&include=storecredit';
		$ch = curl_init($url); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		if(!empty($res)){
			$res = json_decode($res,true);
			foreach ($res['data'] as $v) {
				if (isset($v['id']) && ($v['id'] === $customer_id)) {
					$data = $v;
				}
			}
			return $data;
		}
	}
	return $data;
}

function updateBCCustomerStoreCredit($email_id,$token_validation_id,$invoice_id){
    $conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$token_validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		$acess_token = $result['acess_token'];
		$store_hash = $result['store_hash'];
					
		$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id=?");
		$stmt_order_payment->execute([$invoice_id]);
		$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
		$result_order_payment = $stmt_order_payment->fetchAll();
		//print_r($result_order_payment);exit;
		if (isset($result_order_payment[0])) {
			$result_order_payment = $result_order_payment[0];
			$string               = base64_decode($result_order_payment['params'], true);
			$string               = preg_replace("/[\r\n]+/", ' ', $string);
			$json                 = utf8_encode($string);
			$cartData             = json_decode($json, true);

			$grandTotal        = $cartData['grand_total'];
			$invalidCondition = (! ($cartData['cart']['customer_id'] > 0));
			if ($invalidCondition) {
				return;
			}
			$invalidCondition = (!isset($cartData['isStoreCreditApplied'])  && !($cartData['isStoreCreditApplied'] === 'true'));
			if ($invalidCondition) {
				return;
			}
			$customerData = getBCCustomerData($email_id, $token_validation_id, $cartData['cart']['customer_id']);
			$invalidCondition = empty($customerData);
			if ($invalidCondition) {
				return;
			}
			$invalidCondition = !isset($customerData['store_credit_amounts'], $customerData['store_credit_amounts'][0]['amount']);
			if ($invalidCondition) {
				return;
			}

			$StoreCreditAmount = $customerData['store_credit_amounts'][0]['amount'];
			$invalidCondition = (! ($StoreCreditAmount > 0));
			if ($invalidCondition) {
				return;
			}
			if ($grandTotal > $StoreCreditAmount) {
				$storeCreditLeft = 0;
			} else {
				$storeCreditLeft = ($StoreCreditAmount - $grandTotal);
			}
			$request_u = [];
			$request_u[] = [
				'id'=>$cartData['cart']['customer_id'],
				'store_credit_amounts'=>[
						[
							'amount'=>$storeCreditLeft
						]
				]
			];
			$jsonRequest = json_encode($request_u,true);
			$url = STORE_URL.$store_hash.'/v3/customers';
			$header = array(
				"store_hash: ".$store_hash,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$res_u = curl_exec($ch);
			curl_close($ch);
		}
	}
}

function previousOrderNotes($email_id,$validation_id,$order_id){
	$previousOrderNotes = '';
	
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		if(!empty($result['client_id']) || !empty($result['client_secret']) || !empty($result['client_terminal_id'])){
			$acess_token = $result['acess_token'];
			$store_hash = $result['store_hash'];
			
			$url_T = STORE_URL.$store_hash.'/v2/orders/'.$order_id; 

			$header = array(
				"store_hash: ".$store_hash,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			$result_T = '';

			$ch_T = curl_init($url_T);
			curl_setopt($ch_T, CURLOPT_HTTPHEADER, $header); // send my headers
			curl_setopt($ch_T, CURLOPT_RETURNTRANSFER, true); // return result in a variable
			curl_setopt($ch_T, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch_T, CURLOPT_SSLVERSION, 3);     
			//curl_setopt($ch_T, CURLOPT_POST, true); 
			//curl_setopt($ch_T, CURLOPT_POSTFIELDS, $post_details);
			curl_setopt($ch_T, CURLOPT_FOLLOWLOCATION, true);               

			$result_T = curl_exec($ch_T);
			//print_r($result_T);exit;
			$check_errors = json_decode($result_T);
			if(isset($check_errors->errors)){
			}else{
				if(json_last_error() === 0){
					$response = json_decode($result_T,true);
					if(isset($response['staff_notes'])){
						$previousOrderNotes = $response['staff_notes'];
					}
				}
			}
		}
	}
	return $previousOrderNotes;
}