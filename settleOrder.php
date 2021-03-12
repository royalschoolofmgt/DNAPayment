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

if(isset($_REQUEST['bc_email_id'])){
	$email_id = $_REQUEST['bc_email_id'];
	$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	if (isset($result[0])) {
		$result = $result[0];
		if(empty($result['client_id']) || empty($result['client_secret']) || empty($result['client_terminal_id'])){
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}
$invoice_id = '';
if(isset($_REQUEST['auth'])){
	$invoice_id = json_decode(base64_decode($_REQUEST['auth']));
}else{
	header("Location:dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Responsive bootstrap landing template">
        <meta name="keywords" content="247Commerce ">
		<meta name="author" content="247commerce.co.uk">

        <!-- <link rel="shortcut icon" href="images/favicon.ico"> -->

        <title>DNA</title>

        <!-- owl carousel css -->
        <link rel="stylesheet" type="text/css" href="css/owl.carousel.min.css" />
        <link rel="stylesheet" type="text/css" href="css/owl.theme.default.min.css" />        

        <!-- Bootstrap core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <link href="css/remixicon.css" rel="stylesheet">

        <!-- Custom styles for this template -->
        <link href="scss/style.css" rel="stylesheet">
    </head>
	<body>
		<section class="connect-with" style="margin-bottom: 0">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-lg-12">
						<div style="height: 135px;">
							<div class="float-left"><h3>Settle Client Transaction</h3></div>
							<div class="float-right"><h3><a class="btn btn-line" href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" >Dashboard</a></h3></div>
						</div>
						<div class="facts-box feeds-box" style="padding: 0; border-radius: 10px;">
							<?php
								$conn = getConnection();
								$refunded_amount = 0;
								$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id='".$_REQUEST['bc_email_id']."' and invoice_id='".$invoice_id."' and refund_status='REFUND'");
								$ref_stmt->execute();
								$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
								$ref_result = $ref_stmt->fetchAll();
								if (count($ref_result) > 0) {
									foreach($ref_result as $k=>$v){
										$refunded_amount += $v['refund_amount'];
									}
								}
								
								$stmt = $conn->prepare("SELECT * FROM order_payment_details opd,order_details od WHERE opd.order_id = od.invoice_id and opd.order_id='".$invoice_id."'");
								$stmt->execute();
								$stmt->setFetchMode(PDO::FETCH_ASSOC);
								$result = $stmt->fetchAll();
								if (count($result) > 0) {
									$result = $result[0];
								?>
									<table class="table product-feed">
										<tbody>
											<tr class="no-border">
												<td>Email Id</td>
												<td><?= $result['email_id'] ?></td>
												<td>Invoice Id</td>
												<td><?= $result['invoice_id'] ?></td>
											</tr>
											<tr class="no-border">
												<td>Amount</td>
												<td><?= $result['currency'].' '.$result['total_amount'] ?></td>
												<td>Status</td>
												<td><?= $result['status'] ?></td>
											</tr>
											<tr class="no-border">
												<td>Amount Paid</td>
												<td><?= $result['currency'].' '.$result['amount_paid'] ?></td>
												<td>Settlement Status</td>
												<td><?= $result['settlement_status'] ?></td>
											</tr>
											<?php if($result['settlement_status'] != "CHARGE"){ ?>
												<tr class="no-border">
													<td colspan=2>
														<form action="proceedSettle.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" method="POST" >
															<input type="hidden" name="invoice_id" value="<?= $result['invoice_id'] ?>" />
															<div class="sign-btn"><button type="submit" class="btn btn-primary">Settle</button><br></div>
														</form>
													</td>
													<td colspan=2></td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								<?php
								}else{
									echo "No Data Found";
								}
							?>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- js placed at the end of the document so the pages load faster -->
        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap.bundle.min.js"></script>
        <!-- Jquery easing -->                                                      
        <script src="js/jquery.easing.min.js"></script>
        <!-- Owl carousel js -->
        <script src="js/owl.carousel.min.js"></script>

        <!-- carousel init -->
        <script src="js/carousel.init.js"></script>
        <!--common script for all pages-->
        <script src="js/jquery.app.js"></script>
    </body>
</html>
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
			alert("Settlement Processed Successfully");
		}else if(error == 1){
			alert("Settlement Processed Failed");
		}else if(error == 2){
			alert("Something Went Wrong");
		}
	}
});
</script>