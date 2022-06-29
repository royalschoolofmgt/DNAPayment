<?php
/**
	* Feed List Page
	* Author 247Commerce
	* Date 30 SEP 2020
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['247authsess'])){
	header("Location:index.php");
}
$invoice_id = '';
if(isset($_REQUEST['auth'])){
	$invoice_id = json_decode(base64_decode($_REQUEST['auth']));
}else{
	header("Location:dashboard.php");
}
require_once('db-config.php');
require_once('config.php');
require_once('header.php');
require_once('d-header.php');
?>

<section class="connect-with" style="margin-bottom: 0">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-12">
                <div style="height: 135px;">
					<div class="float-left"><h3>Refund Client Transaction</h3></div>
				</div>
				<div class="facts-box feeds-box" style="padding: 0; border-radius: 10px;">
					<?php
						$conn = getConnection();
						$stmt = $conn->prepare("SELECT * FROM order_payment_details opd,order_details od WHERE opd.order_id = od.invoice_id and opd.order_id='".$invoice_id."'");
						$stmt->execute();
						$stmt->setFetchMode(PDO::FETCH_ASSOC);
						$result = $stmt->fetchAll();
						if (count($result) > 0) {
							$result = $result[0];
							
							$refunded_amount = 0;
							$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id='".$result['email_id']."' and invoice_id='".$invoice_id."' and refund_status='REFUND'");
							$ref_stmt->execute();
							$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
							$ref_result = $ref_stmt->fetchAll();
							if (count($ref_result) > 0) {
								foreach($ref_result as $k=>$v){
									$refunded_amount += $v['refund_amount'];
								}
							}
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
										<td colspan=2>Amount Refunded Already <?= $result['currency'].' '.$refunded_amount ?> </td>
										<td colspan=2></td>
									</tr>
									<tr class="no-border">
										<td colspan=2>
											<form action="proceedRefund.php" method="POST" >
												Enter Amount to be Refunded
												<input type="hidden" name="invoice_id" value="<?= $result['invoice_id'] ?>" />
												<p><input class="form-control" type="number" name="refund_amount" value="" min=1 max="<?= $result['total_amount'] ?>" /></p>
												<div class="sign-btn"><button type="submit" class="btn btn-primary">Sign in</button><br></div>
											</form>
										</td>
										<td colspan=2></td>
									</tr>
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
<?php
require_once('footer.php');
?>
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
			alert("Refund Processed Successfully");
		}else if(error == 1){
			alert("Refund Processed Failed");
		}else if(error == 2){
			alert("Something Went Wrong");
		}
	}
});
</script>