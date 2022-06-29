<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
/**
	* Feed List Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once('db-config.php');
require_once('config.php');

$conn = getConnection();

if(isset($_REQUEST['bc_email_id']) && isset($_REQUEST['key'])){
	$email_id = $_REQUEST['bc_email_id'];
	$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
	$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		if($result['is_test_live'] == '1'){
			if(empty($result['client_id']) && empty($result['client_secret']) && empty($result['client_terminal_id'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}else{
			if(empty($result['client_id_test']) && empty($result['client_secret_test']) && empty($result['client_terminal_id_test'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}
$invoice_id = '';
if(isset($_REQUEST['auth'])){
	$invoice_id = json_decode(base64_decode($_REQUEST['auth']));
}else{
	header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>DNA Payments</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400i" rel="stylesheet">

    <!-- font-awesome css-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.1/css/all.css" integrity="sha384-O8whS3fhG2OnA5Kas0Y9l3cfpmYjapjI0E4theH4iuMD+pLhbf6JI0jIMfYcK3yZ" crossorigin="anonymous">

    <!-- Google font-Poppins css-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">


    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link href="css/media.css" rel="stylesheet">
	<link rel="stylesheet" href="css/toaster/toaster.css">
	<link rel="stylesheet" href="css/247loader.css">

</head>

<body style="background-color: #f9f9fa;">

<section class="inner-top">
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center logo"> <img src="images/logo.png" alt="logo" class="img-responsive"></div>
		</div>
	</div>
</section>

<section class="order-details">

	<div class="container">
	
		<div class="row">
		
			<div class="col-md-6 col-8 text-left"><h4>Settle Client Transaction</h4></div>
			<div class="col-md-6 col-4 text-right">
				<a href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>">
					<h5><i class="fas fa-arrow-left"></i> Back To Dashboard</h5>
				</a>
			</div>
		</div>
		
		<div class="row rows">
			<?php
				$conn = getConnection();
				$refunded_amount = 0;
				$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id=? and invoice_id=? and refund_status='REFUND'");
				$ref_stmt->execute([$_REQUEST['bc_email_id'],$invoice_id]);
				$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
				$ref_result = $ref_stmt->fetchAll();
				if (count($ref_result) > 0) {
					foreach($ref_result as $k=>$v){
						$refunded_amount += $v['refund_amount'];
					}
				}
				
				$stmt = $conn->prepare("SELECT * FROM order_payment_details opd,order_details od WHERE opd.order_id = od.invoice_id and opd.order_id=?");
				$stmt->execute([$invoice_id]);
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$result = $stmt->fetchAll();
				if (count($result) > 0) {
					$result = $result[0];
				?>
				<form id="proceedRefund" action="proceedCancel.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" method="POST" >
			<div class="order-details-bg settle">
			
				<div class="col-md-12 s-conetnt">
				
					<div class="row">
					
						<div class="col-md-4">
						<p>Email Id</p>
						<h3><?= $result['email_id'] ?></h3>
						</div>
						<div class="col-md-3">
						<p>Invoice Id</p>
						<h3><?= $result['invoice_id'] ?></h3>
						</div>
						<div class="col-md-3">
						<p>Amount</p>
						<h3><?= $result['currency'].' '.$result['total_amount'] ?></h3>
						</div>
						<div class="col-md-2">
						<p>Status</p>
						<span class="<?= ($result['status'] == 'CANCELLED')?'badges':'badges2' ?>"><?= ucfirst($result['status']) ?></span>
						</div>
					</div>
				</div>
				<div class="col-md-12 pt22">
					<div class="row custom-width">
						<div class="col-md-10">
						</div>
						<div class="col-md-2">
							<div>
								<?php if($result['status'] == 'CANCELLED'){ ?>
							<a href="orderDetails.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" class="btn1">Back</a>
						<?php }else{ ?>
							<input type="hidden" name="invoice_id" value="<?= $result['invoice_id'] ?>" />
							<button type="submit" class="btn1">Cancel Order</button>
						<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			</form>
			<?php
				}else{
					echo "No Data Found";
				}
			?>
		</div>
	
	
	</div>




</section>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script type="text/javascript" charset="utf8" src="js/toaster/jquery.toaster.js"></script>
<script type="text/javascript" charset="utf8" src="js/247loader.js"></script>
<script>
var text = "Please wait...";
var current_effect = "bounce";
$('input[name="chkOrgRow"]').on('change', function() {
  $(this).closest('tr').toggleClass('yellow', $(this).is(':checked'));
});


</script>
<script>
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};
$(document).ready(function(){
	var error = getUrlParameter('error');
	if(error){
		if(error == 0){
			$.toaster({ priority : "success", title : "Success", message : "Order Cancelled Successfully" });
		}else if(error == 1){
			$.toaster({ priority : "danger", title : "Error", message : "Order Cancellation Failed" });
		}else if(error == 2){
			$.toaster({ priority : "danger", title : "Error", message : "Something Went Wrong" });
		}
	}
	$('body').on('click','.showTrans',function(e){
		e.preventDefault();
		if($('body #demoTrans').hasClass("collapse")){
			$('body #demoTrans').removeClass("collapse");
		}else{
			$('body #demoTrans').addClass("collapse");
		}
	});
	$('body').on('submit','#proceedRefund',function(e){
		$("body").waitMe({
			effect: current_effect,
			text: text,
			bg: "rgba(255,255,255,0.7)",
			color: "#000",
			maxSize: "",
			waitTime: -1,
			source: "images/img.svg",
			textPos: "vertical",
			fontSize: "",
			onClose: function(el) {}
		});
	});
});
</script>
<style>
	
</style>
</body>

</html>