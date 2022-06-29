<?php
if(!isset($_SESSION)){
	session_start();
}
if(isset($_SESSION['247authsess'])){
	header("Location:dashboard.php");
}
require_once('config.php');
require_once('db-config.php');
require_once('header.php');
?>

<section class="connect-with">
	<div class="container">
		<div class="facts-box login p-3">
			<div class="media-body align-self-center connect-box">
				<p><img src="../images/247-logo.jpg" width="180" alt="img" class=""></p>
				<p><h2 style="text-align: center;">DNA Payment Login</h2></p>
				<form action="validateSignin.php" method="POST" id="tokenValidationForm" >
					<p><input type="text" class="form-control" name="email_id"></p>
					<p><input type="password" class="form-control" name="password"></p>
					<div class="sign-btn"><button type="submit" class="btn btn-primary">Sign in</button><br></div>
				</form>
			</div>
		</div>
	</div>
</section>
<?php
require_once('footer.php');
?>


