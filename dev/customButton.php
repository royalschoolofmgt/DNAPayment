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
			if(empty($result['client_id']) && empty($result['client_secret']) && empty($result['client_terminal_id'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}else{
			if(empty($result['client_id_test']) && empty($result['client_secret_test']) && empty($result['client_terminal_id_test'])){
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
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
	<link rel="stylesheet" href="css/toaster/toaster.css">

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
		
			<div class="col-md-6 col-8 text-left"><h4>Custom Payment Button</h4></div>
			<div class="col-md-6 col-4 text-right">
				<a href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id']."&key=".$_REQUEST['key'] ?>">
					<h5><i class="fas fa-arrow-left"></i> Back To Dashboard</h5>
				</a>
			</div>
		
		</div>
		<div class="row rows">
			<div class="order-details-bg settle">
				<form action="updateCustomButton.php" method="POST" >
					<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
					<input type="hidden" name="key" value="<?= @$_REQUEST['key'] ?>" />
					<?php
						$container_id = '.checkout-step--payment .checkout-view-header';
						$css_prop = '#dnapaymentForm>button{
	display:block;
	background-color: #00FF00 !important;
	color: #000000 !important;
	border-color: #FF0000 !important;
}';
						
						$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
						$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id=? and token_validation_id=?");
						$stmt_c->execute([$_REQUEST['bc_email_id'],$validation_id]);
						$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
						$result_c = $stmt_c->fetchAll();
						if(count($result_c) > 0){
							$result_c = $result_c[0];
						}else{
							$result_c['container_id'] = $container_id;
							$result_c['css_prop'] = $css_prop;
						}
						//print_r($result_c);exit;
						$enable = '';
						if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == "1"){
							$enable = "checked";
						}
					?>
					<p>CSS selector of the previous sibling HTML element
					<font style="font-size:8px;">(Payment button will be placed after this HTML element)</font></p>
					<input type="text" name="container_id" id="container_id" required value="<?= @$result_c['container_id'] ?>" class="form-control" placeholder="Container Id / Class">
					<br/>
					Css Properties 
					<textarea name="css_prop" id="css_prop" class="signin form-control" style="height: 150px;" placeholder=".button{display:block;}"><?= @$result_c['css_prop'] ?></textarea>
					<br/>
					<input type="checkbox" name="is_enabled" <?= $enable ?> />    Enable Custom Button 
					<div class="text-right"><button type="button" id="resetCustom" class="btn btn-order">Reset</button>&nbsp;&nbsp;&nbsp;<button type="submit" class="btn btn-order">Save</button></div>
				</form>
			</div>
		</div>
	</div>
</section>
<script src="js/jquery.min.js"></script>
<script type="text/javascript" charset="utf8" src="js/toaster/jquery.toaster.js"></script>
<script>
var id = '<?= $container_id ?>';
var css = '<?= base64_encode($css_prop) ?>';
$('body').on('click','#resetCustom',function(){
	$('body #container_id').val(id);
	$('body #css_prop').val(window.atob(css));
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
	var updated = getUrlParameter('updated');
	if(updated){
		$.toaster({ priority : "success", title : "Success", message : "DNAPayments Custom buuton updated for your Store,Please wait for some time and check the changes" });
	}
});
</script>
</body>

</html>