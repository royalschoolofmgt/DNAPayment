<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once('config.php');
require_once('db-config.php');

header('access-control-allow-origin: *');

$final_data = array();
$final_data['status'] = false;
$final_data['data'] = array();
$final_data['msg'] = '';
	
if(isset($_REQUEST['authKey'])){
	$conn = getConnection();
	$invoiceId = json_decode(base64_decode($_REQUEST['authKey']),true);
	if($invoiceId != ""){
		$stmt_order_payment = $conn->prepare("select * from order_payment_details where order_id='".$invoiceId."'");
		$stmt_order_payment->execute();
		$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
		$result_order_payment = $stmt_order_payment->fetchAll();
		if (isset($result_order_payment[0])) {
			$result_order_payment = $result_order_payment[0];
			if($result_order_payment['status'] != "CONFIRMED"){
				$final_data['status'] = true;
				$final_data['msg'] = "Payment Unsuccessful, Please try Again";
			}
		}
	}
}
echo json_encode($final_data,true);exit;

