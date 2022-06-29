<?php    
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


	function getConnection(){
		$username = "dn_payments21";
		$password = "24Dna$8P@mt*";
		$database = "dna_pay";
		$host = "newmageaurora-final.co13c6zl8ys8.eu-west-1.rds.amazonaws.com";
		//$conn = mysqli_connect($host,$username,$password,$database);
		
		$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		return $conn;
	}

$conn = getConnection();
$email_id = @$_REQUEST['bc_email_id'];
$validation_id = json_decode(base64_decode(@$_REQUEST['key']),true);
$sql_count = "SELECT * from order_payment_details";
$stmt_res = $conn->prepare($sql_count);
$stmt_res->execute([$email_id,$validation_id]);
$stmt_res->setFetchMode(PDO::FETCH_ASSOC);
$result_final = $stmt_res->fetchAll();
print_r(json_encode($result_final,true));exit;
?>