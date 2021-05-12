<?php
if (!isset($_SESSION)) {
	session_start();
}
require_once('config.php');
require_once('db-config.php');
	////$data = json_decode(file_get_contents('php://input'), true);
	$data = $_REQUEST;
	$fp = fopen("load.txt", "w");
	fwrite($fp, serialize($data));
	fclose($fp);

	$jsonData = verifySignedRequest($_GET['signed_payload']);
	/*print '<pre />';
	print_r($jsonData);*/

	function verifySignedRequest($signedRequest) {
		$client_secret = APP_CLIENT_SECRET;
		list($encodedData, $encodedSignature) = explode('.', $signedRequest, 2);

		// decode the data
		$signature = base64_decode($encodedSignature);
		    $jsonStr = base64_decode($encodedData);
		$data = json_decode($jsonStr, true);

		// confirm the signature
		$expectedSignature = hash_hmac('sha256', $jsonStr, $client_secret, $raw = false);
		if (!hash_equals($expectedSignature, $signature)) {
		    error_log('Bad signed request from BigCommerce!');
		    return null;
		}

		return $data;
	}


//show HTML if signed_payload match
if($jsonData != null && $jsonData != "") {
	$email = @$jsonData['user']['email'];
	$store_hash = @$jsonData['store_hash'];
	
	$conn = getConnection();
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and store_hash=?");
	$stmt->execute([$email,$store_hash]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
		
	if (count($result) > 0) {
		$result = $result[0];
		header("Location: index.php?bc_email_id=".$email."&key=".base64_encode(json_encode($result['validation_id'],true))); 
	}

}
?>

