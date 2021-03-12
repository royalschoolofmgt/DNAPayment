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
							<div class="float-left"><h3>Order Details</h3></div>
							<div class="float-right"><h3><a class="btn btn-line" href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" >Dashboard</a></h3></div>
						</div>
						<div class="facts-box feeds-box" style="padding: 0; border-radius: 10px;">
							<table class="table product-feed">
								<thead>
								  <tr class="no-border">
									<th scope="col"><span class="grey font-14">Invoice Id</span></th>
									<th scope="col"><span class="grey font-14">BigCommerce Order Id</span></th>
									<th scope="col"><span class="grey font-14">Payment type</span></th>
									<th scope="col"><span class="grey font-14">Payment Status</span></th>
									<th scope="col"><span class="grey font-14">Settlement Status</span></th>
									<th scope="col"><span class="grey font-14">Currency</span></th>
									<th scope="col"><span class="grey font-14">Total</span></th>
									<th scope="col"><span class="grey font-14">Amount Paid</span></th>
									<th scope="col"><span class="grey font-14">Created Date</span></th>
									<th scope="col"><span class="grey font-14">Actions</span></th>
								  </tr>
								</thead>
								<tbody>
									<?php 
										/* getting feed data from table */
										$conn = getConnection();
										$stmt = $conn->prepare("SELECT opd.settlement_status,opd.type,opd.amount_paid,opd.email_id as email,opd.order_id as invoice_id,od.order_id,opd.status,opd.currency,opd.total_amount,opd.created_date FROM order_payment_details opd LEFT JOIN order_details od ON opd.order_id = od.invoice_id WHERE opd.email_id='".$email_id."' order by opd.id desc");
										$stmt->execute();
										$stmt->setFetchMode(PDO::FETCH_ASSOC);
										$result = $stmt->fetchAll();
										if (count($result) > 0) {
												foreach($result as $k=>$v) {
													?>
													<tr>
														<td scope="row"  data-label="Invoice Id" class="font-16"><?= $v['invoice_id'] ?></td>
														<td data-label="Order Id"><?= $v['order_id'] ?></td>
														<td data-label="Order Id"><?= $v['type'] ?></td>
														<td data-label="Order Id"><?= $v['status'] ?></td>
														<td data-label="Order Id"><?= $v['settlement_status'] ?></td>
														<td data-label="Currency"><?= $v['currency'] ?></td>
														<td data-label="Total"><?= $v['total_amount'] ?></td>
														<td data-label="Total"><?= $v['amount_paid'] ?></td>
														<td data-label="Date"><?= date("Y-m-d h:i A",strtotime($v['created_date']))?></td>
														<td data-label="Actions">
															<?php
																if($v['status'] == "CONFIRMED"){
																	$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id='".$_REQUEST['bc_email_id']."' and invoice_id='".$v['invoice_id']."' and refund_status='REFUND'");
																	$ref_stmt->execute();
																	$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
																	$ref_result = $ref_stmt->fetchAll();
																	if (count($ref_result) > 0) { ?>
																		<a class="btn btn-line" href="#" >Refunded</a>
																	<?php }else{ ?>
																	<a class="btn btn-line" href="refundOrder.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>&auth=<?= base64_encode(json_encode($v['invoice_id'])) ?>" >Refund</a>
																<?php } ?>
															<?php } ?>
															<?php
																if($v['status'] == "CONFIRMED" && $v['type'] == "AUTH" && $v['settlement_status'] == "PENDING"){
															?>
																<a class="btn btn-line" href="settleOrder.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>&auth=<?= base64_encode(json_encode($v['invoice_id'])) ?>" >Settle</a>
															<?php }else if($v['status'] == "CONFIRMED" && $v['type'] == "AUTH" && $v['settlement_status'] == "CHARGE"){ ?>
																<a class="btn btn-line" href="#" >Settled</a>
															<?php } ?>
														</td>
													  </tr>
										<?php	}
										}
									?>
		  
								</tbody>
							</table>

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