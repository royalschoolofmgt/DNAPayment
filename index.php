<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<?php
/**
	* Initial Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
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
		if(!empty($result['client_id']) && !empty($result['client_secret']) && !empty($result['client_terminal_id'])){
			header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
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
                    <div class="col-lg-4">
                        <div class="facts-box p-5">
                            <div class="media-body align-self-center connect-box">
                                <form action="validateToken.php" method="POST" >
									<div>
										<span class="">
											<input type="hidden" name="bc_email_id" value="<?= @$_REQUEST['bc_email_id'] ?>" />
											Client Id <input type="text" name="client_id" class="signin form-control" placeholder="Client Id" required="required">
											Client Secret <input type="text" name="client_secret" class="signin form-control" placeholder="Client Secret" required="required">
											Client Terminal Id <input type="text" name="client_terminal_id" class="signin form-control" placeholder="Terminal Id" required="required">
										</span>
										 <a href="#" target="_blank">How can i get my clinet id,client secret and terminal id?</a>
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