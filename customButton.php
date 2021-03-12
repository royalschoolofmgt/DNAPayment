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
                    <div class="col-lg-8">
						<div style="height: 135px;">
							<div class="float-left"><h3>Custom Payment Button</h3></div>
							<div class="float-right"><h3><a class="btn btn-line" href="dashboard.php?bc_email_id=<?= $_REQUEST['bc_email_id'] ?>" >Dashboard</a></h3></div>
						</div>
                        <div class="facts-box p-5">
                            <div class="media-body align-self-center connect-box">
                                <form action="updateCustomButton.php" method="POST" >
									<div>
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
										<span class="">
											<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
											Container Id <input type="text" value="<?= @$result_c['container_id'] ?>" name="container_id" class="signin form-control" placeholder="#Container Id" required="required">
											Css Properties <textarea name="css_prop" class="signin form-control" placeholder=".button{display:block;}"><?= @$result_c['css_prop'] ?></textarea>
											<br/>
											<input type="checkbox" name="is_enabled" <?= $enable ?> />    Enable Custom Button 
										</span>
									</div>
									<div><button type="submit" class="btn btn-primary">Submit</button><br></div>
								</form>
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
		

    </body>
</html>