<?php
/**
	* Token Validation Page
	* Author 247Commerce
	* Date 30 SEP 2020
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once('config.php');
require_once('db-config.php');

if(isset($_REQUEST['bc_email_id'])){
	$con = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	if(!empty($email_id)){
		$sql = "select * from dna_token_validation where email_id='".$email_id."'";
		$result = $con->query($sql);
		if ($result->num_rows > 0) {
			$result = $result->fetch_assoc();
			if(!empty($result['client_id']) && !empty($result['client_secret']) && !empty($result['client_terminal_id'])){
				$sellerdb = $result['sellerdb'];
				$acess_token = $result['acess_token'];
				$store_hash = $result['store_hash'];
				$res = createScripts($sellerdb,$acess_token,$store_hash,$email_id);
				if($res == "1"){
					$usql = "update dna_token_validation set is_enable=1 where email_id='".$_REQUEST['bc_email_id']."'";
					//echo $usql;exit;
					$con->query($usql);
				}
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
			}else{
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
			}
		}else{
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}else{
	header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
}

function createScripts($sellerdb,$acess_token,$store_hash,$email_id){
	$con = getConnection();
	$url = array();
	$rStatus = 0;
	$url[] = DNA_SDK;
	$url[] = JS_SDK;
	$url[] = BASE_URL.$sellerdb.'/custom_script.js';
	foreach($url as $k=>$v){
		//$auth_token = '4ir2j1tpf5cw3pzx7ea4ual2jrei8cd';
		$header = array(
			"X-Auth-Client: ".$acess_token,
			"X-Auth-Token: ".$acess_token,
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$location = 'head';
		$cstom_url = BASE_URL.$sellerdb.'/custom_script.js';
		if($v == $cstom_url){
			$location = 'footer';
		}
		$request = '{
		  "name": "DNAApp",
		  "description": "DNA payment files",
		  "html": "<script src=\"'.$v.'\"></script>",
		  "auto_uninstall": true,
		  "load_method": "default",
		  "location": "'.$location.'",
		  "visibility": "checkout",
		  "kind": "script_tag",
		  "consent_category": "essential"
		}';
		//print_r($request);exit;
		$url = STORE_URL.$store_hash.'/v3/content/scripts';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		//print_r($res);exit;
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","script_tag_injection","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
		//echo $log_sql;exit;
		$con->query($log_sql);
		if(!empty($res)){
			$response = json_decode($res,true);
			if(isset($response['data']['uuid'])){
				$sql = 'insert into dna_scripts(script_email_id,script_filename,script_code,status,api_response) values("'.$email_id.'","'.basename($v).'","'.$response['data']['uuid'].'","1","'.addslashes($res).'")';
				//echo $sql;exit;
				$con->query($sql);
				$rStatus++;
			}
		}
	}
	if($rStatus >= 3){
		return 1;
	}
	if($rStatus >= 3){
		return 0;
	}
}
?>