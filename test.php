<?php    
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

$conn = getConnection();
$email_id = $_REQUEST['bc_email_id'];
$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
$sql_del = "DELETE from order_payment_details where id=8";
$conn->exec($sql_del);
$sql_count = "SELECT * from order_payment_details";
$stmt_res = $conn->prepare($sql_count);
$stmt_res->execute([$email_id,$validation_id]);
$stmt_res->setFetchMode(PDO::FETCH_ASSOC);
$result_final = $stmt_res->fetchAll();
print_r(json_encode($result_final,true));exit;
?>