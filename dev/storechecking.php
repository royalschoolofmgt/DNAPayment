<?php
require_once('config.php');
require_once('db-config.php');
require_once('store_helper.php');

updateBCCustomerStoreCredit1('bigi@247commerce.co.uk','1','DNA-1-61cc74a7c92d2-1640789159');

function updateBCCustomerStoreCredit1($email_id,$token_validation_id,$invoice_id){
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
			//print_r($customerData);exit;
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
				revolut_log_message('error', 'BigCommerceHelper-updateBCCustomerStoreCredit:storeCreditAmount is empty',$validation_id);
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