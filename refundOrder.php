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
		if(empty($result['client_id']) || empty($result['client_secret']) || empty($result['client_terminal_id'])){
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
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
				<form id="proceedRefund" action="proceedRefund.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" method="POST" >
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
						<span class="badges2"><?= ucfirst($result['status']) ?></span>
						</div>
					</div>
				</div>
					<div class="col-md-12 s-conetnt">
					<div class="row">
						<?php if($refunded_amount == 0){ ?>
						<div class="col-md-2">
						<p class="pt10">Enter Amount to be Refunded</p>
						</div>
						<div class="col-md-5">
							<input type="hidden" name="invoice_id" value="<?= $result['invoice_id'] ?>" />
							<p><input class="form-control" type="number" required name="refund_amount" oninput="validity.valid||(value='');" step=any value="" min=1 max="<?= $result['total_amount']-$refunded_amount ?>" />(Amount can be Refunded only one time).</p>
						</div>
						<?php } ?>
						<div class="col-md-3 amount-refund visible-lg">
						<p>Amount Refunded Already <strong><?= $result['currency'].' '.$refunded_amount ?></strong></p>
						</div>
						<div class="col-md-2 visible-lg">
						<a href="#" class="showTrans" >See Transactions</a>
						</div>
						<div class="col-md-3 visible-xs amount">
						<p>Amount Refunded Already</p>
						<a href="#" class="showTrans">See Transactions</a>
						</div>
					</div>
				</div>
				<div class="col-md-12 s-conetnt collapse" id="demoTrans">
					<?php
						$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id=? and invoice_id=?");
						$ref_stmt->execute([$_REQUEST['bc_email_id'],$invoice_id]);
						$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
						$ref_result = $ref_stmt->fetchAll();
						if (count($ref_result) > 0) {
					?>
					<table>
						<tr>
							<th>Refund Amount</th>
							<th>Refund Status</th>
							<th>Created Date</th>
						<tr>
						<?php
							foreach($ref_result as $rk=>$rv){
						?>
						<tr>
							<td><?= $rv['refund_amount'] ?></td>
							<td><?= $rv['refund_status'] ?></td>
							<td><?= date("d-m-Y h:i A",strtotime($rv['created_date'])) ?></td>
						</tr>
						<?php } ?>
					</table>
					<?php
						}else{
							echo "No Data Found";
						}
					?>
				</div>
				<?php if($refunded_amount == 0){ ?>
				<div class="col-md-12 pt22">
					<div class="row">
						<div class="col-md-10">
						</div>
						<div class="col-md-2">
						<a href="orderDetails.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" class="btn2">Cancel</a><button type="submit" class="btn1">Refund</abutton
						</div>
					</div>
				</div>
				<?php } ?>
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
			$.toaster({ priority : "success", title : "Success", message : "Refund Processed Successfully" });
		}else if(error == 1){
			$.toaster({ priority : "danger", title : "Error", message : "Refund Processed Failed" });
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

</body>

</html>