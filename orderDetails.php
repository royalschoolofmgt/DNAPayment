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
		<link rel="stylesheet" type="text/css" href="css/datatable/jquery.dataTables.min.css">
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
					<div class="col-md-6 col-6 text-left">
					  <h4>Order Details</h4>
					</div>
					<div class="col-md-6 col-6 text-right">
						<a href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>">
							<h5><i class="fas fa-arrow-left"></i> Back To Dashboard</h5>
						</a>
					</div>
				</div>
				<div class="row rows">
					<div class="order-details-bg">
						<div class="col-md-12">
							<div class="row ">
								<div class="col-xl-11 col-12">
									<input type="email" class="form-control1" id="exampleInputEmail1" placeholder="Search by BigCommerce Order ID">
								</div>
								<div class="col-xl-1 none" style="display:none">
									<div class="dropdown">
										<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
											Action
										</button>
										<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
											<li><a class="dropdown-item" href="#">Action</a></li>
											<li><a class="dropdown-item" href="#">Another action</a></li>
											<li><a class="dropdown-item" href="#">Something else here</a></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-12">
							<!-- <div id="no-more-tables" class="table-responsive"> -->
							<div class="table-responsive">
								<table class="table" id="orderdetails_dashboard" >
									<thead class="cf">
										<tr id="table_columns">
											<th><input type="checkbox" class="form-check-input" id="exampleCheck1"></th>
											<th>Payment Number</th>
											<th class="numeric none">Bigcommerce <br>order id</th>
											<th class="numeric">payment<br> type</th>
											<th class="numeric">payment<br> status</th>
											<th class="numeric">settlement<br> status</th>
											<th class="numeric">currency</th>
											<th class="numeric">total</th>
											<th class="numeric">amount<br> paid</th>
											<th class="numeric">Created date</th>
											<th class="numeric">Action</th>
										</tr>
									</thead>
									<tbody id="table_data_rows">
									  
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<script src="js/jquery.min.js"></script>
		<script src="js/bootstrap.bundle.min.js"></script>
		<script type="text/javascript" charset="utf8" src="js/datatable/jquery.dataTables.min.js"></script>
		<script type="text/javascript" charset="utf8" src="js/datatable/datatable-responsive.js"></script>
        <script src="js/order-details.js?v=1.00"></script>
		<script type="text/javascript" charset="utf8" src="js/247loader.js"></script>
		<style>
			.paging_simple_numbers{
				display: flex;
			}
		</style>
		<script>
			var text = "Please wait...";
			var current_effect = "bounce";
			$('input[name="chkOrgRow"]').on('change', function() {
				$(this).closest('tr').toggleClass('yellow', $(this).is(':checked'));
			});
			var app_base_url = "<?= BASE_URL ?>";
			var email_id = "<?= $_REQUEST['bc_email_id'] ?>";
			var key = "<?= $_REQUEST['key'] ?>";
			$(document).ready(function(){
				X247OrderDetails.main_data('scripts/orderdetails_processing.php?email_id='+email_id+'&key='+key,'orderdetails_dashboard');
			});
			$('body').on('click','.btn-line',function(e){
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
         
         
		</script>
	</body>
</html>