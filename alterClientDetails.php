<?php
/**
	* Alter Client Details Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

if(isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && isset($_REQUEST['client_terminal_id'])){
	$conn = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
	if(!empty($email_id) && !empty($validation_id)){
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if (count($result) > 0) {
			if(!empty($_REQUEST['client_id']) && !empty($_REQUEST['client_secret']) && !empty($_REQUEST['client_terminal_id'])){
				$result = $result[0];
				$sellerdb = $result['sellerdb'];
				//alterFile($sellerdb,$paystack_key);
				
				$sql = 'update dna_token_validation set client_id=?,client_secret=?,client_terminal_id=? where email_id=? and validation_id=?';
				// Prepare statement
				$stmt = $conn->prepare($sql);

				// execute the query
				$stmt->execute([$_REQUEST['client_id'],$_REQUEST['client_secret'],$_REQUEST['client_terminal_id'],$email_id,$validation_id]);
				
				$conn->query($sql);
				
			}
		}
	}
}
echo 1;
/* creating tables Based on Seller */
function alterFile($sellerdb,$paystack_key){
}
?>