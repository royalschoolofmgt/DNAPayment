<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
/**
	* Initial Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');

/*creating DB connection */
$conn = getConnection();

/* check zoovu token is validated or not 
	If already Verified redirect to Home Page
*/
$validation_id = '';
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
		if($result['is_test_live'] == '1'){
			if(!empty($result['client_id']) && !empty($result['client_secret']) && !empty($result['client_terminal_id'])){
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}else{
			if(!empty($result['client_id_test']) && !empty($result['client_secret_test']) && !empty($result['client_terminal_id_test'])){
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}
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
	</head>
	<body style="background-color: #1b1734;">
		<div class="container">
			<div class="">
				<div class="d-flex justify-content-center align-items-center flex-column" style="height: 100vh;">
					<img src="images/logo.png" alt="logo" class="img-responsive">
					<div class="col-md-8 white-bg">
						<h4>Getting Started</h4>
						<p>Enter credentials</p>
						<?php
							$error=0;
							if(isset($_REQUEST['error']) && $_REQUEST['error'] == 1){
								$error=1;
							} 
						?>
						<div><span id="error_show" style="color:red;<?= ($error == 1)?'':'display:none;' ?>" >Please provide valid details.</span></div>
						<form action="validateToken.php" method="POST" >
							<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
							<input type="hidden" name="key" value="<?= @$_REQUEST['key'] ?>" />
							<div class="d-flex switch-group mb-3">
								<div class="btn-group btn-toggle togglebox"  role="group">
									<button class="btn btn-sm active style="width: 100px;" type="button" >SANDBOX</button>
									<button class="btn btn-sm" type="button"  style="width: 100px;" role="button">LIVE</button>
									<input class="form-check-input" name="is_test_live" type="hidden" id="is_test_live" value="0" >
								</div>
							</div>
							<input type="text" name="client_id" required value="" class="form-control" placeholder="Client ID">
							<div class="form-group">
								<div class="input-group">
									<input type="text" class="form-control" name="client_secret" required id="exampleInputAmount" placeholder="Client Secret">
								</div>
							</div>
							<input type="text" name="client_terminal_id" required value="" class="form-control" placeholder="Terminal ID"><br/>
							<span>How can I <a href="https://developer.dnapayments.com/docs/ecommerce/ecommerce-plugins/getting-started/" target="_blank">get my client id, client secret and terminal id?</a></span>
							<button type="submit" class="btn btn-submit">Submit</button>
						</form>
					</div>
				</div>
			</div><!--/.row-->
		</div>
	</body>
</html>
<style>
.togglebox .active {
    background-color: #0464f4!important;
    border-color: #0464f4!important;
    color: #FFF!important;
}
</style>
<script src="js/jquery.min.js"></script>
<script>
$('.btn-toggle').click(function() {
    $(this).find('.btn').toggleClass('active');
    const val = $('body #is_test_live').val();
    if(val == "1"){
        $('body #is_test_live').val(0);
    }else{
        $('body #is_test_live').val(1);
    }

    if ($(this).find('.btn-info').length>0) {
        $(this).find('.btn').toggleClass('btn-info');
    }

    $('body #testAPIDiv').toggle('show');
    $('body #liveAPIDiv').toggle('show');
    $('body #saveChangesButton').show();

});
</script>