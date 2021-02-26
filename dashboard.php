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

$con = getConnection();
if(isset($_REQUEST['bc_email_id'])){
	$email_id = $_REQUEST['bc_email_id'];
	$sql = "select * from dna_token_validation where email_id='".$email_id."'";
	$result = $con->query($sql);
	if ($result->num_rows > 0) {
		$result = $result->fetch_assoc();
		if(empty($result['client_id']) || empty($result['client_secret']) || empty($result['client_terminal_id'])){
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
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
        <section class="connect-with">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div style="height: 135px;">
                            <div class="float-left"><h3>DNA Details</h3></div>
                        </div>
                        <div class="facts-box" style="padding: 0; border-radius: 10px;">


                            <table class="table product-feed">
                                <thead>
                                  <tr class="no-border">
                                    <th scope="col"><span class="grey font-14">Name</span></th>
                                    <th scope="col"><span class="grey font-14">Client Id</span></th>
                                    <th scope="col"><span class="grey font-14">Client Secret</span></th>
                                    <th scope="col"><span class="grey font-14">Terminal Id</span></th>
                                    <th scope="col"><span class="grey font-14">Actions</span></th>
                                  </tr>
                                </thead>
                                <tbody>
								<?php 
									/* getting feed data from table */
									$con = getConnection();
									$email_id = @$_REQUEST['bc_email_id'];
									$sql_token = "select * from dna_token_validation where email_id='".$email_id."'";
									//echo $sql_token;exit;
									$result_token = $con->query($sql_token);
									if ($result_token->num_rows > 0) {
										while($v = $result_token->fetch_assoc()) {
											$enabled = false;
											if($v['is_enable'] == 1){
												$enabled = true;
											}
									?>
										<tr>
											<th scope="row"><?= $v['email_id'] ?></th>
											<td scope="row">
												<div id="changeText" style="display:none;">
													<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_id'] ?>" id="changeTextInput" >&nbsp;
													<img data-key="<?= $v['client_id'] ?>" style="height:40px;" src="images/icons/tick.jpg" class="saveKey save" />&nbsp;
													<img style="height:25px;" id="cancelKey" src="images/icons/cancel.svg" />
												</div>
												<div id="tableText"><?= $v['client_id'] ?></div>
											</td>
											<td scope="row">
												<div id="changeText1" style="display:none;">
													<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_secret'] ?>" id="changeTextInput1" >&nbsp;&nbsp;&nbsp;
													<img data-key="<?= $v['client_secret'] ?>" style="height:40px;" src="images/icons/tick.jpg" class="saveKey save1" />&nbsp;&nbsp;&nbsp;
													<img style="height:25px;" id="cancelKey1" src="images/icons/cancel.svg" />
												</div>
												<div id="tableText1"><?= $v['client_secret'] ?></div>
											</td>
											<td scope="row">
												<div id="changeText2" style="display:none;">
													<input type="text" class="form-control" style="width: 50%;display:inline" value="<?= $v['client_terminal_id'] ?>" id="changeTextInput2" >&nbsp;&nbsp;&nbsp;
													<img data-key="<?= $v['client_terminal_id'] ?>" style="height:40px;" src="images/icons/tick.jpg" class="saveKey save2" />&nbsp;&nbsp;&nbsp;
													<img style="height:25px;" id="cancelKey2" src="images/icons/cancel.svg" />
												</div>
												<div id="tableText2"><?= $v['client_terminal_id'] ?></div>
											</td>
											<td>
												<?php if($enabled){ ?>
													<span><a href="#" class="btn btn-line green alreadyEnabled" > Enabled</a></span>
												<?php }else{ ?>
													<span><a href="enable.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" class="btn btn-line" > Enable</a></span>
												<?php } ?>
												<span><a href="#" class="btn btn-line deletePaystack" > Disable</a></span>
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
            </div>
        </section>
		<!-- Modal -->
		<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
			  <div class="modal-content">
				<div class="modal-header">
				  <h5 class="modal-title" id="exampleModalLongTitle"><span><img src="images/icons/trash-purple.svg" style="margin-top: -5px;"></span> <span class="purple">Disable Paystack</span>  </h5>
				  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				  </button>
				</div>
				<div class="modal-body" id="modalContent">
				  Are you sure you want to disable <strong>DNA?</strong>.
				</div>
				<div class="modal-footer">
				  <button type="button" class="btn btn-line" data-dismiss="modal" style="padding: 12px 20px;">Cancel</button>
				  <button type="button" class="btn btn-primary" id="deleteConfirm">Disable</button>
				</div>
			  </div>
			</div>
		  </div>

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
var bc_email_id = "<?= @$_REQUEST['bc_email_id'] ?>";
$(document).ready(function(){
	$('body').on('click','.alreadyEnabled',function(e){
		e.preventDefault();
		alert("Paystack already Enabled");
	});
	$('body').on('click','.deletePaystack',function(e){
		$('body #exampleModalCenter').modal('show');
	});
	$('body').on('click','#deleteConfirm',function(e){
		var url = 'disable.php?bc_email_id='+bc_email_id;
		window.location.href = url;
	});
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
		$('body #changeText12').hide();
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
				url: 'alterClientDetails.php?bc_email_id='+bc_email_id,
				data:{client_id:client_id,client_secret:client_secret,client_terminal_id:client_terminal_id},
				success: function (res) {
					$('body .save').attr('data-key',client_id)
					$('body .save1').attr('data-key',client_secret)
					$('body .save2').attr('data-key',client_terminal_id)
					alert("changed successfully");
					$('body #tableText').text(client_id);
					$('body #tableText1').text(client_secret);
					$('body #tableText2').text(client_terminal_id);
				}
			});
		}
	});
});
</script>
