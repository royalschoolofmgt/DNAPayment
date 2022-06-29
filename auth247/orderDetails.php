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
$email_id = '';
if(isset($_REQUEST['auth'])){
	$email_id = json_decode(base64_decode($_REQUEST['auth']));
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
					<div class="float-left"><h3>Clinet Details(<?= $email_id ?>)</h3></div>
				</div>
				<div class="facts-box feeds-box" style="padding: 0; border-radius: 10px;">
					<table class="table product-feed">
                        <thead>
                          <tr class="no-border">
                            <th scope="col"><span class="grey font-14">Invoice Id</span></th>
                            <th scope="col"><span class="grey font-14">BigCommerce Order Id</span></th>
                            <th scope="col"><span class="grey font-14">Payment Status</span></th>
                            <th scope="col"><span class="grey font-14">Currency</span></th>
                            <th scope="col"><span class="grey font-14">Total</span></th>
                            <th scope="col"><span class="grey font-14">Created Date</span></th>
                            <th scope="col"><span class="grey font-14">Actions</span></th>
                          </tr>
                        </thead>
                        <tbody>
							<?php 
								/* getting feed data from table */
								$conn = getConnection();
								$stmt = $conn->prepare("SELECT opd.order_id as invoice_id,od.order_id,opd.status,opd.currency,opd.total_amount,opd.created_date FROM order_payment_details opd LEFT JOIN order_details od ON opd.order_id = od.invoice_id and opd.email_id='".$email_id."'");
								$stmt->execute();
								$stmt->setFetchMode(PDO::FETCH_ASSOC);
								$result = $stmt->fetchAll();
								if (count($result) > 0) {
										foreach($result as $k=>$v) {
											?>
											<tr>
												<td scope="row"  data-label="Invoice Id" class="font-16"><?= $v['invoice_id'] ?></td>
												<td data-label="Order Id"><?= $v['order_id'] ?></td>
												<td data-label="Order Id"><?= $v['status'] ?></td>
												<td data-label="Currency"><?= $v['currency'] ?></td>
												<td data-label="Total"><?= $v['total_amount'] ?></td>
												<td data-label="Date"><?= date("Y-m-d h:i A",strtotime($v['created_date']))?></td>
												<td data-label="Actions">
													<?php
														if($v['status'] == "CONFIRMED"){
													?>
														<a class="btn btn-line" href="refundOrder.php?auth=<?= base64_encode(json_encode($v['invoice_id'])) ?>" >Refund</a>
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
<?php
require_once('footer.php');
?>