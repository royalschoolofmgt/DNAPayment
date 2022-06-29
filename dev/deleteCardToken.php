<?php	
	/**
		* Token Validation Page
		* Author 247Commerce
		* Date 30 SEP 2020
	*/
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	if (isset($_SERVER['HTTP_ORIGIN'])) {
	    header("Access-Control-Allow-Origin: *");
	    header('Access-Control-Allow-Credentials: true');
	}

	require_once('config.php');
	require_once('db-config.php');
	require_once('store_helper.php');

	require 'log-autoloader.php';

	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;

	$res = array();
	$res['msg'] = '';

	$logger = new Logger('Card_Token_Delete');
	$logger->pushHandler(new StreamHandler('var/logs/DNA_card_token_delete.txt', Logger::INFO));
	$logger->info("id: ".$_REQUEST['id']);

	if(isset($_REQUEST['id'])){
		$logger->info("Before deleting card token for id - ".$_REQUEST['id']);
		$conn = getConnection();

		$stmt_card_token = $conn->prepare("select * from card_token where id=?");						
		$stmt_card_token->execute([$_REQUEST['id']]);
		$stmt_card_token->setFetchMode(PDO::FETCH_ASSOC);
		$result_card_token = $stmt_card_token->fetchAll();
		//print_r($result[0]);exit;
		if (isset($result_card_token[0])) {

			$stmt_card_token_del = $conn->prepare("delete from card_token where id=?");						
			
			$delStatus = $stmt_card_token_del->execute([$_REQUEST['id']]);

			$res['msg'] = $delStatus;
			
			if($delStatus) {
				$logger->info("After deleting card token for id - ".$_REQUEST['id']);
			}			
		}
	}

	echo json_encode($res);exit;

?>