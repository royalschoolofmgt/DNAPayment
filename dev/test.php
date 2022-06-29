<?php    
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

$conn = getConnection();

$invoiceId = 'DNA-2-61ea61872c9b4-1642750343';
$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id=?");
$stmt_order_payment->execute([$invoiceId]);
$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
$result_order_payment = $stmt_order_payment->fetchAll();
//print_r($result_order_payment);exit;
if (isset($result_order_payment[0])) {
	$result_order_payment = $result_order_payment[0];
	$data = stripslashes($result_order_payment['api_response']);
	$data = json_decode($data,true);
	$string = base64_decode($result_order_payment['params']);
	$string = preg_replace("/[\r\n]+/", " ", $string);
	$json = utf8_encode($string);
	$cartData = json_decode($json,true);
	$items_total = 0;
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id']]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		$acess_token = $result['acess_token'];
		$store_hash = $result['store_hash'];
		
		$customer_email_id = @$cartData['cart']['email'];
		$customer_id = @$cartData['cart']['customer_id'];
		$merchant_card_token = @$data['cardTokenId'];
		$merchant_card_name = @$data['cardholderName'];
		$merchant_card_schema = @$data['cardSchemeName'];
		$merchant_card_pan = @$data['cardPanStarred'];
		$merchant_card_schema_id = @$data['cardSchemeId'];
		$merchant_card_expiry = @$data['cardExpiryDate'];
		if(empty($merchant_card_name)){
			$merchant_card_name = $merchant_card_schema;
		}
		if($customer_id > 0 && !empty($merchant_card_token)){
			try{
				$stmt_card_token = $conn->prepare("select * from card_token where email_id=? and token_validation_id=? and customer_id=? and customer_email_id=? and merchant_card_token=?");
				$stmt_card_token->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id'],$customer_id,$customer_email_id,$merchant_card_token]);
				$stmt_card_token->setFetchMode(PDO::FETCH_ASSOC);
				$result_card_token = $stmt_card_token->fetchAll();
				if (isset($result_card_token[0])) {
				}else{
					$card_token_sql = 'insert into card_token(email_id,token_validation_id,customer_id,customer_email_id,merchant_card_token,merchant_card_name,merchant_card_schema,merchant_card_pan,merchant_card_schema_id,merchant_card_expiry) values(?,?,?,?,?,?,?,?,?,?)';
					
					$stmt_card_token_insert = $conn->prepare($card_token_sql);
					$stmt_card_token_insert->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id'],$customer_id,$customer_email_id,$merchant_card_token,$merchant_card_name,$merchant_card_schema,$merchant_card_name,$merchant_card_schema_id,$merchant_card_expiry]);
				}
			}catch(\Exception $e){
				print_r($e->getMessage());exit;
			}
		}
	}
}

?>