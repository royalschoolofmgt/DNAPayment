<?php
/**
	* Token Validation Page
	* Author 247Commerce
	* Date 30 SEP 2020
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');
require_once('config.php');
require_once('db-config.php');

$res = array();
$res['status'] = false;
$res['data'] = '';
$res['msg'] = '';

if(isset($_REQUEST['authKey'])){
	$valid = validateAuthentication($_REQUEST);
	if($valid){
		$email_id = json_decode(base64_decode($_REQUEST['authKey']));
		if (filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
			$sql = "select * from dna_token_validation where email_id='".$email_id."'";
			$con = getConnection();
			$result = $con->query($sql);
			if ($result->num_rows > 0) {
				$result = $result->fetch_assoc();
				if(!empty($result['client_id']) && !empty($result['client_secret']) && !empty($result['client_terminal_id'])){
					$invoiceId = "247dna_".time();
					$request = array(
						"scope" => "payment integration_seamless",
						"client_id" => $result['client_id'],
						"client_secret" => $result['client_secret'],
						"grant_type" => "client_credentials",
						"invoiceId" => $invoiceId,
						"amount" => $_REQUEST['totalAmount'],
						"currency" => "GBP",
						"terminal" => $result['client_terminal_id']
					);
					$api_response = oauth2_token($email_id,$request);
					if(isset($api_response['response'])){
						$res['status'] = true;
						$data = array(
									"invoiceId" => $invoiceId,
									"backLink" => "https://bigcommerce.247commerce.co.uk/dna_payment/success.php",
									"failureBackLink" => "https://bigcommerce.247commerce.co.uk/dna_payment/failure.php",
									"postLink" => "https://example.com/update-order",
									"failurePostLink" => "https:example.com/order/759345/fail",
									"language" => "EN",
									"description" => "Order payment",
									"accountId" => "testuser",
									"phone" => "01234567890",
									"terminal" => $result['client_terminal_id'],
									"amount" => $_REQUEST['totalAmount'].'.00',
									"currency" => "GBP",
									"accountCountry" => "GB",
									"accountCity" => "London",
									"accountStreet1" => "14 Tottenham Court Road",
									"accountEmail" => "test@test.com",
									"accountFirstName" => "Paul",
									"accountLastName" => "Smith",
									"accountPostalCode" => "W1T 1JY",
									"auth" => $api_response['response']
								);
						$res['data'] = base64_encode(json_encode($data));
						
					}else{
						$res['msg'] = 'Something went wrong! Please check the data or try again later.';
					}
					
				}
			}
		}
	}
}
echo json_encode($res);exit;

function validateAuthentication($request){
	$valid = true;
	if(isset($request['totalAmount']) && $request['totalAmount'] > 0){
		
	}else{
		$valid = false;
	}
	if(isset($request['authKey'])){
		
	}else{
		$valid = false;
	}
	if(isset($request['currency'])){
		
	}else{
		$valid = false;
	}
	return $valid;
}
function oauth2_token($email_id,$request){
	$con = getConnection();
	$header = array(
		"Accept: application/json",
		"Content-Type: application/json"
	);

	//print_r($request);exit;
	$url = AUTHENTICATE_URL;
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
	$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","DNA","Authentication","'.addslashes($url).'","'.addslashes(json_encode($request)).'","'.addslashes($res).'")';
	//echo $log_sql;exit;
	$con->query($log_sql);
	
	$data = array();
	$data['request'] = $request;
	if(!empty($res)){
		$data = json_decode($res,true);
		if(isset($data['access_token'])){
			$data['response'] = $data;
		}
	}
	
	return $data;
}
?>