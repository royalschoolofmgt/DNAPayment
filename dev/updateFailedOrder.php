<?php

require_once('config.php');
require_once('db-config.php');

require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$data = file_get_contents('php://input');

// create a log channel
$logger = new Logger('Failure PostLink Update Order');
$logger->pushHandler(new StreamHandler('var/logs/DNA_failed_order.txt', Logger::INFO));
$logger->info("Failure PostLink Callback data: ".$data);

if(!empty($data)){
	$data = json_decode($data,true);
	if(isset($data['success']) && $data['success'] == false){
		$conn = getConnection();
		$invoiceId = $data['invoiceId'];
		$usql = 'update order_payment_details set status = ?,api_response=? where order_id=?';
		$stmt = $conn->prepare($usql);
		$stmt->execute(["FAILED",addslashes(json_encode($data)),$invoiceId]);
	}
}

?>