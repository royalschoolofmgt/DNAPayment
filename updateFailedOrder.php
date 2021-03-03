<?php

require_once('config.php');
require_once('db-config.php');

$data = file_get_contents('php://input');
try{
	$fp = fopen("order.txt", "w");
	fwrite($fp, $data);
	fclose($fp);
}catch(Exception $e) {
}

if(!empty($data)){
	$data = json_decode($data,true);
	if(isset($data['success']) && $data['success'] == false){
		$conn = getConnection();
		$invoiceId = $data['invoiceId'];
		$usql = 'update order_payment_details set status = "FAILED",api_response="'.addslashes(json_encode($data)).'" where order_id="'.$invoiceId.'"';
		$stmt = $conn->prepare($usql);
		$stmt->execute();
	}
}

?>