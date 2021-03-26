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
if(isset($_REQUEST['bc_email_id'])){
	$email_id = $_REQUEST['bc_email_id'];
	$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$result = $stmt->fetchAll();
	//print_r($result[0]);exit;
	if (isset($result[0])) {
		$result = $result[0];
		if(empty($result['client_id']) && empty($result['client_secret']) && empty($result['client_terminal_id'])){
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
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
		
			<div class="col-md-6 col-8 text-left"><h4>Custom Payment Button</h4></div>
			<div class="col-md-6 col-4 text-right">
				<a href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>">
					<h5><i class="fas fa-arrow-left"></i> Back To Dashboard</h5>
				</a>
			</div>
		
		</div>
		<div class="row rows">
			<div class="order-details-bg settle">
				<form action="updateCustomButton.php" method="POST" >
					<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
					<?php
						$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id='".$_REQUEST['bc_email_id']."'");
						$stmt_c->execute();
						$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
						$result_c = $stmt_c->fetchAll();
						if(count($result_c) > 0){
							$result_c = $result_c[0];
						}
						//print_r($result_c);exit;
						$enable = '';
						if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == "1"){
							$enable = "checked";
						}
					?>
					Container Id / Class
					<input type="text" name="container_id" required value="<?= @$result_c['container_id'] ?>" class="form-control" placeholder="Container Id / Class">
					<br/>
					Css Properties 
					<textarea name="css_prop" class="signin form-control" placeholder=".button{display:block;}"><?= @$result_c['css_prop'] ?></textarea>
					<br/>
					<input type="checkbox" name="is_enabled" <?= $enable ?> />    Enable Custom Button 
					<button type="submit" class="btn btn-submit">Save</button>
				</form>
			</div>
		</div>
	</div>
</section>
</body>

</html>