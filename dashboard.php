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
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
	<link rel="stylesheet" href="css/toaster/toaster.css">

</head>
<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <img src="images/logo.png" alt="logo" class="img-responsive">
                </div>

                <div class="col-md-12 marTP-30">
                    <span class="title-head">
                        Dashboard
                    </span>

                    <span class="btn-secion">
                        <a class="btn btn-yellow" href="customButton.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" >Custom Payment Button</a>
                        <a class="btn btn-order" href="orderDetails.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" >Order Details</a>
                    </span>
                </div>                
            </div>
        </div>
    </header>
	<style>
		##toaster{
			top:40% !important;
			right:40% !important;
			width: 400px !important;
		}
	</style>
    <section class="order-section">
        <div class="container">
            <div class="row">

                <div class="white-bg dash-head">
					<form action="updateSettings.php" method="POST" >
					<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
					<input type="hidden" name="key" value="<?= @$_REQUEST['key'] ?>" />
                    <div class="col-md-12">
						<?php 
							$payment_option = 'CFO';
							/* getting feed data from table */
							$con = getConnection();
							$email_id = @$_REQUEST['bc_email_id'];
							$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
							$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
							$stmt->execute([$email_id,$validation_id]);
							$stmt->setFetchMode(PDO::FETCH_ASSOC);
							$result_token = $stmt->fetchAll();
							
							if (count($result_token) > 0) {
								foreach($result_token as $k=>$v){
									$payment_option = $v['payment_option'];
									$enabled = false;
									if($v['is_enable'] == 1){
										$enabled = true;
									}
							?>
								<ul class="user-detail">
									<li>
										<!--<h5 class="user-head">BigCommerce Email</h5>
										<p class="user-para">
											<?= $v['email_id'] ?>
										</p>-->

										<h5 class="user-head">Client Id</h5>
										
											<div id="changeText" style="display:none;">
												<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_id'] ?>" id="changeTextInput" >&nbsp;
												<img data-key="<?= $v['client_id'] ?>" style="height:20px;" src="images/check.png" class="saveKey save" />&nbsp;
												<img style="height:19px;" id="cancelKey" src="images/cross.png" />
											</div>
											<div id="tableText"><p class="user-para"><?= $v['client_id'] ?></p></div>
										
									</li>
									<li>
										<h5 class="user-head">Client Secret</h5>
										
											<div id="changeText1" style="display:none;">
												<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_secret'] ?>" id="changeTextInput1" >&nbsp;&nbsp;&nbsp;
												<img data-key="<?= $v['client_secret'] ?>" style="height:20px;" src="images/check.png" class="saveKey save1" />&nbsp;&nbsp;&nbsp;
												<img style="height:19px;" id="cancelKey1" src="images/cross.png" />
											</div>
											<div id="tableText1"><p class="user-para"><?= $v['client_secret'] ?></p></div>
										
									</li>
									<li>
										<h5 class="user-head">Terminal Id</h5>
										
											<div id="changeText2" style="display:none;">
												<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_terminal_id'] ?>" id="changeTextInput2" >&nbsp;&nbsp;&nbsp;
												<img data-key="<?= $v['client_terminal_id'] ?>" style="height:20px;" src="images/check.png" class="saveKey save2" />&nbsp;&nbsp;&nbsp;
												<img style="height:19px;" id="cancelKey2" src="images/cross.png" />
											</div>
											<div id="tableText2"><p class="user-para"><?= $v['client_terminal_id'] ?></p></div>
										
									</li>
									<li>
										<h5 class="user-head">Payment Options</h5>
										
										<div class="radio">
											<label class="radio-container">
											<input type="radio" name="payment_option" <?= ($payment_option == "CFO")?'checked':'' ?> value="CFO" >
											<span class="radio-checkmark"></span>
											Capture on order placed
										</label>
										</div>
										<div class="radio">
											<label class="radio-container">
												<input type="radio" name="payment_option" <?= ($payment_option == "CFS")?'checked':'' ?> value="CFS" />
												<span class="radio-checkmark"></span>
												Capture on shipment
											</label>
										</div>
									</li>
									<li>
										<h5 class="user-head">Checkout</h5>
										<label class="switch">
										  <input id="actionChange" type="checkbox" <?= ($enabled)?'checked':'' ?> value="<?= ($enabled)?'1':'0' ?>" />
										  <span class="slider round"></span>
										</label>
									</li>
								</ul>
							<?php
								}
							}
							?>
                    </div>   

                    <div class="col-md-12 section-update">
                        <button type="submit" class="btn btn-order">UPDATE</button>
                    </div>
					</form>
                </div>

                <span class="title-head" style="color: #000; margin-top:30px; ">
                    Order Details

                    <a href="orderDetails.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".@$_REQUEST['key'] ?>" style="float: right;margin-top: 10px;">View all</a>
                </span>

                    

                <div class="white-bg marTP-30 od-block" style="width: 100%;">
                    <form class="row gy-2 gx-3 align-items-center search-form" style="display:none">
                        <div class="col-sm-10 lt-search">
                            <div class="input-group">
                                <div class="input-group-text se-ico"><i class="fas fa-search"></i></div>
                                <input type="text" class="form-control search-box" id="dropdownMenuButton1" placeholder="Order ID">
                            </div>
                        </div>
                        <div class="col-sm-2 rt-search" style="display:none;">
                            
                            <select class="form-select" id="autoSizingSelect">
                                <option selected>Action</option>
                            </select>
                        </div>
                    </form>

                    
                    
                    <table class="table order-table table-responsive-stack" id="tableOne">
                        <thead class="thead-light">
                            <th>Payment Number</th>
                            <th>BigCommerce Order Id</th>
                            <th>Payment type</th>
                            <th>Payment Status</th>
                            <th>Settlement Status</th>
                            <th>Currency</th>
                            <th>Total</th>
                            <th>Amount Paid</th>
                            <th>Created Date</th>
                            <th>Actions</th>  
                        </thead>   

                        <tbody>
							<?php
								$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
								$sql_res = "SELECT opd.id,opd.settlement_status,opd.type,opd.amount_paid,opd.email_id as email,opd.order_id as invoice_id,od.order_id,opd.status,opd.currency,opd.total_amount,opd.created_date FROM order_payment_details opd LEFT JOIN order_details od ON opd.order_id = od.invoice_id WHERE opd.email_id=? and opd.token_validation_id=? order by opd.id desc LIMIT 0,15";
								$stmt_res = $conn->prepare($sql_res);
								$stmt_res->execute([$_REQUEST['bc_email_id'],$validation_id]);
								$stmt_res->setFetchMode(PDO::FETCH_ASSOC);
								$result_final = $stmt_res->fetchAll();
								if(count($result_final) > 0){
									foreach($result_final as $k=>$values) {
							?>
									<tr>
										<td>
											<?= $values['invoice_id'] ?>
										</td>
										<td><?= $values['order_id'] ?></td>
										<td>
											<?= $values['type'] ?>
										</td>
										<td>
											<?php
												$status = '';
												if($values['status'] == "CONFIRMED"){
													$status = '<span class="badges1">Confirmed</span>';
												}else{
													$status = '<span class="badges">'.ucfirst($values['status']).'</span>';
												}
											?>
											<?= $status ?>
										</td>
										<td>
											<?php
												$sstatus = '';
												if($values['type'] == "SALE"){
													$sstatus = '';
												}else{
													if($values['settlement_status'] == "CHARGE"){
														$sstatus = '<span class="badges1">'.ucfirst($values['settlement_status']).'</span>';
													}else{
														$sstatus = '<span class="badges">'.ucfirst($values['settlement_status']).'</span>';
													}
												}
											?>
											<?= $sstatus ?>
										</td>
										<td>
											<?= $values['currency'] ?>
										</td>
										<td>
											<?= $values['total_amount'] ?>
										</td>
										<td>
											<?= $values['amount_paid'] ?>
										</td>
										<td><?= date("Y-m-d h:i A",strtotime($values['created_date'])) ?></td>
										<td>
											<?php
												$actions = '';
													if($values['status'] == "CONFIRMED" && $values['type'] == "AUTH" && ($values['settlement_status'] == "PENDING" || $values['settlement_status'] == "FAILED")){
														$actions .= '<a style="width: 100%;margin-bottom: 5px;" class="btn btn-line" href="settleOrder.php?bc_email_id='.$_REQUEST['bc_email_id'].'&key='.$_REQUEST['key'].'&auth='.base64_encode(json_encode($values['invoice_id'])).'" ><button style="width: 100%;" type="button" class="btn btn-outline-primary">Settle</button></a>';
													}else if($values['status'] == "CONFIRMED" && $values['type'] == "AUTH" && $values['settlement_status'] == "CHARGE"){
														$actions .= '<button type="button" class="btn btn-outline-success" style="width: 100%;margin-bottom: 5px;" disabled >Settled</button>';
														$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id='".$_REQUEST['bc_email_id']."' and invoice_id='".$values['invoice_id']."' and refund_status='REFUND'");
														$ref_stmt->execute();
														$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
														$ref_result = $ref_stmt->fetchAll();
														if (count($ref_result) > 0) {
															$actions .= '<button type="button" style="width: 100%;margin-bottom: 5px;" class="btn btn-outline-success" disabled >Refunded</button>';
														}else{
															$actions .= '<a class="btn btn-line" style="width: 100%;margin-bottom: 5px;" href="refundOrder.php?bc_email_id='.$_REQUEST['bc_email_id'].'&key='.$_REQUEST['key'].'&auth='.base64_encode(json_encode($values['invoice_id'])).'" ><button style="width: 100%;" type="button" class="btn btn-outline-primary">Refund</button></a>';
														}
													}else if($values['status'] == "CONFIRMED"){
														$ref_stmt = $conn->prepare("SELECT * FROM order_refund where email_id='".$_REQUEST['bc_email_id']."' and invoice_id='".$values['invoice_id']."' and refund_status='REFUND'");
														$ref_stmt->execute();
														$ref_stmt->setFetchMode(PDO::FETCH_ASSOC);
														$ref_result = $ref_stmt->fetchAll();
														if (count($ref_result) > 0) {
															$actions .= '<button type="button" class="btn btn-outline-success" style="width: 100%;margin-bottom: 5px;" disabled >Refunded</a></button>';
														}else{
															$actions .= '<a class="btn btn-line" style="width: 100%;margin-bottom: 5px;" href="refundOrder.php?bc_email_id='.$_REQUEST['bc_email_id'].'&key='.$_REQUEST['key'].'&auth='.base64_encode(json_encode($values['invoice_id'])).'" ><button style="width: 100%;" type="button" class="btn btn-outline-primary">Refund</button></a>';
														}
													}
											?>
											<?= $actions ?>
										</td>
									</tr>
							<?php
									}
								}
							?>
                        </tbody>                         
                    </table>
                    
                </div>
            </div>
        </div>
    </section>
    <!-- <div class="container">
        <div class="row">
            <div class="d-flex justify-content-center align-items-center flex-column" style="height: 100vh;">
               
               <div class="col-md-4 white-bg">
                   <h4>Getting Started</h4>
                   <p>Login to the dashboard</p>

                   <form>
                       <input type="text" name="" value="" class="form-control" placeholder="Client ID">

                       <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" id="exampleInputAmount" placeholder="Client Secret">
                                <div class="input-group-addon input-eye"><i class="far fa-eye"></i></div>
                            </div>
                        </div>

                        <input type="text" name="" value="" class="form-control" placeholder="Terminal ID">

                        <span>How can I <a href="#">get my client id, client secret and terminal id?</a></span>

                        <button type="submit" class="btn btn-submit">Submit</button>
                   </form>
               </div>
            </div>
        </div><!--/.row-->
	<!-- </div> -->
	<!-- Modal -->
		<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
			  <div class="modal-content">
				<div class="modal-header">
				  <h5 class="modal-title" id="exampleModalLongTitle"><span><img src="images/icons/trash-purple.svg" style="margin-top: -5px;"></span> <span class="purple">Remove DNA Payments from Checkout</span>  </h5>
				  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<img style="height:25px;" src="images/cross.png" />
				  </button>
				</div>
				<div class="modal-body" id="modalContent">
				  Are you sure you want to disable DNA Payments? </strong>
				</div>
				<div class="modal-footer">
				  <button type="button" class="btn btn-order" id="cancelConfirm" data-dismiss="modal">Cancel</button>
				  <button type="button" class="btn btn-order" id="deleteConfirm">Disable</button>
				</div>
			  </div>
			</div>
		  </div>
	<script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" charset="utf8" src="js/toaster/jquery.toaster.js"></script>

	<script type="text/javascript">
		var bc_email_id = "<?= @$_REQUEST['bc_email_id'] ?>";
		var key = "<?= @$_REQUEST['key'] ?>";
		$(document).ready(function() {
			// inspired by http://jsfiddle.net/arunpjohny/564Lxosz/1/
			$('.table-responsive-stack').each(function (i) {
				var id = $(this).attr('id');
				//alert(id);
				$(this).find("th").each(function(i) {
					$('#'+id + ' td:nth-child(' + (i + 1) + ')').prepend('<span class="table-responsive-stack-thead">'+             $(this).text() + ':</span> ');
					$('.table-responsive-stack-thead').hide();
             
				});
			});
			$( '.table-responsive-stack' ).each(function() {
			  var thCount = $(this).find("th").length; 
			   var rowGrow = 100 / thCount + '%';
			   //console.log(rowGrow);
			   $(this).find("th, td").css('flex-basis', rowGrow);   
			});
			function flexTable(){
				if ($(window).width() < 900) {
					$(".table-responsive-stack").each(function (i) {
					  $(this).find(".table-responsive-stack-thead").show();
					  $(this).find('thead').hide();
					});
				// window is less than 768px 
				} else {
					$(".table-responsive-stack").each(function (i) {
						$(this).find(".table-responsive-stack-thead").hide();
						$(this).find('thead').show();
					});
				}
				// flextable   
			}      
			flexTable();
			window.onresize = function(event) {
				flexTable();
			};
			$('body').on('click','#tableText',function(e){
				$(this).hide();
				$('body #changeText').show();
			});
			$('body').on('click','#tableText1',function(e){
				$(this).hide();
				$('body #changeText1').show();
			});
			$('body').on('click','#tableText2',function(e){
				$(this).hide();
				$('body #changeText2').show();
			});
			$('body').on('click','#cancelKey',function(e){
				$('body #changeText').hide();
				$('body #tableText').show();
			});
			$('body').on('click','#cancelKey1',function(e){
				$('body #changeText1').hide();
				$('body #tableText1').show();
			});
			$('body').on('click','#cancelKey2',function(e){
				$('body #changeText2').hide();
				$('body #tableText2').show();
			});
			$('body').on('click','.saveKey',function(e){
				var prev_key = $('body #changeTextInput').attr('data-key');
				var prev_key1 = $('body #changeTextInput1').attr('data-key');
				var prev_key2 = $('body #changeTextInput2').attr('data-key');
				var client_id = $('body #changeTextInput').val();
				var client_secret = $('body #changeTextInput1').val();
				var client_terminal_id = $('body #changeTextInput2').val();
				$('body #changeText').hide();
				$('body #changeText1').hide();
				$('body #changeText2').hide();
				$('body #tableText').show();
				$('body #tableText1').show();
				$('body #tableText2').show();
				var post = false;
				if(prev_key != client_id){
					post = true;
				}
				if(prev_key1 != client_secret){
					post = true;
				}
				if(prev_key2 != client_terminal_id){
					post = true;
				}
				if(post){
					$.ajax({
						type: 'POST',
						url: 'alterClientDetails.php?bc_email_id='+bc_email_id+'key='+key,
						data:{client_id:client_id,client_secret:client_secret,client_terminal_id:client_terminal_id},
						success: function (res) {
							$('body .save').attr('data-key',client_id)
							$('body .save1').attr('data-key',client_secret)
							$('body .save2').attr('data-key',client_terminal_id)
							$.toaster({ priority : "success", title : "Success", message : "Details Changed Successfully" });
							$('body #tableText').html('<p class="user-para">'+client_id+'</p>');
							$('body #tableText1').html('<p class="user-para">'+client_secret+'</p>');
							$('body #tableText2').html('<p class="user-para">'+client_terminal_id+'</p>');
						}
					});
				}
			});
			$('body').on('change','#actionChange',function(){
				var val = $(this).val();
				if(val == "0"){
					var url = 'enable.php?bc_email_id='+bc_email_id+'&key='+key;
					window.location.href = url;
				}else{
					$('body #exampleModalCenter').modal('show');
				}
			});
			$('body').on('click','#deleteConfirm',function(e){
				var url = 'disable.php?bc_email_id='+bc_email_id+'&key='+key;
				window.location.href = url;
			});
			$('body').on('click','#cancelConfirm,.close',function(e){
				$('body #exampleModalCenter').modal('hide');
				$('#actionChange').trigger('click');
			});
		});
		var getUrlParameter = function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split("&"),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split("=");

				if (sParameterName[0] === sParam) {
					return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return false;
		};
		$(document).ready(function(){
			var enabled = getUrlParameter('enabled');
			if(enabled){
				$.toaster({ priority : "success", title : "Success", message : "DNA Payments enabled for your Store" });
			}
			var disabled = getUrlParameter('disabled');
			if(disabled){
				$.toaster({ priority : "success", title : "Success", message : "DNA Payments disabled for your Store" });
			}
			var updated = getUrlParameter('updated');
			if(updated){
				$.toaster({ priority : "success", title : "Success", message : "Payment Option Updated" });
			}
		});
	</script>
</body>
</html>