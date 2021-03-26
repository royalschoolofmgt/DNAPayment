<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once('config.php');
require_once('db-config.php');
	
$data = $_REQUEST;
unInstallScripts($data);

function unInstallScripts($data){
	if(isset($data['signed_payload'])) {
		$payload = explode(".",$data['signed_payload']);
		if(isset($payload[0])){
			$signed_payload = $payload[0];
			$userData = json_decode(base64_decode($signed_payload),true);
			if(isset($userData['user']['email'])) {
				
				$email = $userData['user']['email'];
				$conn = getConnection();
				
				$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email."'");
				$stmt->execute();
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$result = $stmt->fetchAll();
				//print_r($result[0]);exit;
				if (isset($result[0])) {
					$result = $result[0];
					deleteScripts($result['sellerdb'],$result['acess_token'],$result['store_hash'],$email);
					deleteCustomPage($result['sellerdb'],$result['acess_token'],$result['store_hash'],$email);
					$usql = "update dna_token_validation set is_enable=0,client_id='',client_secret='',	client_terminal_id='' where email_id='".$email."'";
					//echo $usql;exit;
					$stmt = $conn->prepare($usql);
					$stmt->execute();
				}
			}
		}
	}
}
   
function deleteScripts($sellerdb,$acess_token,$store_hash,$email_id){
	$rStatus = 0;
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_scripts where script_email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (count($result) > 0) {
		foreach($result as $k=>$v){
			//$auth_token = '4ir2j1tpf5cw3pzx7ea4ual2jrei8cd';
			$header = array(
				"X-Auth-Client: ".$acess_token,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			$request = '';
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
			$conn->exec($log_sql);

			$sql = 'delete from dna_scripts where script_id='.$v['script_id'];
			//echo $sql;exit;
			$conn->exec($sql);
		}
		$sql = 'update dna_scripts where script_id='.$v['script_id'];
		//echo $sql;exit;
		$conn->exec($sql);
	}
}
function deleteCustomPage($sellerdb,$acess_token,$store_hash,$email_id){
	$rStatus = 0;
	$conn = getConnection();
	$stmt = $conn->prepare("select * from 247custompages where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (count($result) > 0) {
		foreach($result as $k=>$v){
			$header = array(
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			$request = '';
			//print_r($request);exit;
			$url = STORE_URL.$store_hash.'/v2/pages/'.$v['page_bc_id'];
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
			$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Page Deletion","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
			//echo $log_sql;exit;
			$conn->exec($log_sql);
			if(empty($res)){
				$sql = 'delete from 247custompages where id='.$v['id'];
				//echo $sql;exit;
				$conn->exec($sql);
			}
		}
	}
}

?>