<?php
/**
	* Alter Client Details Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

$return = array();
$return['status'] = false;
$return['msg'] = 'Error in updating Details!..';
if(isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && isset($_REQUEST['client_terminal_id']) && isset($_REQUEST['is_test_live'])){
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
				$valid = validateToken($email_id,$_REQUEST['client_id'],$_REQUEST['client_secret'],$_REQUEST['client_terminal_id'],$validation_id,$_REQUEST['is_test_live']);
				if($valid){
					$payment_option = @$_REQUEST['payment_option'];
					if(empty($payment_option)){
						$payment_option = 'CFO';
					}
					if($_REQUEST['is_test_live'] == '1'){
						$sql = 'update dna_token_validation set payment_option=?,is_test_live=?,client_id=?,client_secret=?,client_terminal_id=? where email_id=? and validation_id=?';
					}else{
						$sql = 'update dna_token_validation set payment_option=?,is_test_live=?,client_id_test=?,client_secret_test=?,client_terminal_id_test=? where email_id=? and validation_id=?';
					}
					// Prepare statement
					$stmt = $conn->prepare($sql);

					// execute the query
					$stmt->execute([$payment_option,$_REQUEST['is_test_live'],$_REQUEST['client_id'],$_REQUEST['client_secret'],$_REQUEST['client_terminal_id'],$email_id,$validation_id]);
					$return['status'] = true;
					$return['msg'] = 'success';
				}else{
					$return['msg'] = 'Please provide valid payment Details';
				}
			}
		}else{
			$return['msg'] = 'Error in updating Details!..';
		}
	}else{
		$return['msg'] = 'Error in updating Details!..';
	}
}else{
	$return['msg'] = 'Invalid Details Provided';
}
echo json_encode($return,true);exit;

/* Validating Token */
function ValidateToken($email_id,$client_id,$client_secret,$client_terminal_id,$validation_id,$is_test_live){
	$conn = getConnection();
	$response = false;
	if(!empty($client_id) && !empty($client_secret) && !empty($client_terminal_id)){
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
						"scope"=>"webapi",
						"client_id"=>$client_id,
						"client_secret"=>$client_secret,
						"grant_type"=>"client_credentials",
					);
		if($is_test_live == '0'){
			$url = AUTHENTICATE_URL_TEST;
		}else{
			$url = AUTHENTICATE_URL;
		}
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
		
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
		$stmt = $conn->prepare($log_sql);
		$stmt->execute([$email_id,"DNA","Validation",addslashes($url),addslashes(json_encode($request,true)),addslashes($res),$validation_id]);
		
		if(!empty($res)){
			$data = json_decode($res,true);
			if(isset($data['access_token'])){
				$response = true;
			}
		}
	}
	return $response;
}
?>