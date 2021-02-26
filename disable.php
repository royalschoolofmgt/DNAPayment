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
				deleteScripts($sellerdb,$acess_token,$store_hash,$email_id);
				$usql = "update dna_token_validation set is_enable=0 where email_id='".$_REQUEST['bc_email_id']."'";
				//echo $usql;exit;
				$con->query($usql);
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

function deleteScripts($sellerdb,$acess_token,$store_hash,$email_id){
	$rStatus = 0;
	$con = getConnection();
	$sql = "select * from dna_scripts where script_email_id='".$email_id."'";
	$result = $con->query($sql);
	if ($result->num_rows > 0) {
		while($v = $result->fetch_assoc()){
			//$auth_token = '4ir2j1tpf5cw3pzx7ea4ual2jrei8cd';
			$header = array(
				"X-Auth-Client: ".$acess_token,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			//print_r($request);exit;
			$url = STORE_URL.$store_hash.'/v3/content/scripts/'.$v['script_code'];
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"DELETE");
			curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$res = curl_exec($ch);
			//print_r($res);exit;
			curl_close($ch);
			$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","script_tag_deletion","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
			//echo $log_sql;exit;
			$con->query($log_sql);
			if(empty($res)){
				$sql = 'delete from dna_scripts where script_id='.$v['script_id'];
				//echo $sql;exit;
				$con->query($sql);
				$rStatus++;
			}
		}
	}
}
?>