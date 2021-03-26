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
	header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
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
               <div class="col-md-6 col-8 text-left">
                  <h4>Settle Client Transaction</h4>
               </div>
               <div class="col-md-6 col-4 text-right">
					<a href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>">
						<h5><i class="fas fa-arrow-left"></i> Back To Dashboard</h5>
					</a>
				</div>
            </div>
            <div class="row rows">
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
                        <div class="col-md-2">
                           <p>Amount Paid</p>
                           <h3><?= $result['currency'].' '.$result['amount_paid'] ?></h3>
                        </div>
                        <div class="col-md-3 visible-lg">
                           <p>Settlement Status</p>
                           <span class="badges2"><?= ucfirst($result['settlement_status']) ?></span>
                        </div>
                     </div>
                  </div>
				  <?php if($result['settlement_status'] != "CHARGE"){ ?>
                  <div class="col-md-12 pt22">
				  <form action="proceedSettle.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" method="POST" >
                     <div class="row">
						
                        <div class="col-md-10">
                        </div>
                        <div class="col-md-2">
                           <a href="#" class="btn2">Cancel</a>
								<input type="hidden" name="invoice_id" value="<?= $result['invoice_id'] ?>" />
								<button type="submit" class="btn1">Settle</button>
							
                        </div>
                     </div>
					 </form>
                  </div>
				  <?php } ?>
				  <?php
					}else{
						echo "No Data Found";
					}
				?>
               </div>
            </div>
         </div>
      </section>
      <script src="js/jquery.min.js"></script>
		<script src="js/bootstrap.bundle.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
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
   </body>
</html>