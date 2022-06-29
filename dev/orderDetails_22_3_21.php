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
		<link rel="stylesheet" type="text/css" href="css/datatable/jquery.dataTables.min.css">
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
							<div class="row" style="border:1px solid #ddd">
								<div class="col-sm-12">
									<div class="table-bordered table-responsive table-bordered hor-scroll-table">
										<table id="orderdetails_dashboard" class="dataTable no-footer table table-bordered table-striped table">
											<thead>
												<tr role="row" class="suceess" id="table_columns">
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
		<script type="text/javascript" charset="utf8" src="js/datatable/jquery.dataTables.min.js"></script>
		<script type="text/javascript" charset="utf8" src="js/datatable/datatable-responsive.js"></script>
        <script src="js/order-details.js?v=1.04"></script>
		
		<script>
			var app_base_url = "<?= BASE_URL ?>";
			var email_id = "<?= $_REQUEST['bc_email_id'] ?>";
			$(document).ready(function(){
				X247OrderDetails.main_data('scripts/orderdetails_processing.php?email_id='+email_id,'orderdetails_dashboard');
			});
		</script>
    </body>
</html>