<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}
require_once('config.php');
require_once('db-config.php');

$postData = $_REQUEST;
if(isset($postData['email_id']) && isset($postData['password'])){
	$conn = getConnection();
	$sql = "select * from user where email_id='".$postData['email_id']."' and password='".$postData['password']."'";
	$stmt_login = $conn->prepare($sql);
	$stmt_login->execute();
	$stmt_login->setFetchMode(PDO::FETCH_ASSOC);
	$result_login = $stmt_login->fetchAll();
	if (isset($result_login[0])) {
		$_SESSION['247authsess'] = $result_login[0]['email_id'];
		header("Location:dashboard.php");
	}else{
		header("Location:index.php");
	}
}else{
	header("Location:index.php");
}

?>