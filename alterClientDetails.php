<?php
/**
	* Alter Client Details Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

if(isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && isset($_REQUEST['client_terminal_id'])){
	$con = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	if(!empty($email_id)){
		$sql = "select * from dna_token_validation where email_id='".$email_id."'";
		$result = $con->query($sql);
		/* If user already Exists checking validating Token Id
			If not validated verify and update the status 
		*/
		if ($result->num_rows > 0) {
			$result = $result->fetch_assoc();
			if(!empty($_REQUEST['client_id']) && !empty($_REQUEST['client_secret']) && !empty($_REQUEST['client_terminal_id'])){
				$sellerdb = $result['sellerdb'];
				//alterFile($sellerdb,$paystack_key);
				$sql = 'update dna_token_validation set client_id="'.$_REQUEST['client_id'].'",client_secret="'.$_REQUEST['client_secret'].'",client_terminal_id="'.$_REQUEST['client_terminal_id'].'" where email_id="'.$email_id.'"';
				$con->query($sql);
				
			}
		}
	}
}
echo 1;
/* creating tables Based on Seller */
function alterFile($sellerdb,$paystack_key){
	$con = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		$filecontent = 'function payWithPaystack(e) {
					let handler = PaystackPop.setup({
						key: "'.$paystack_key.'", // Replace with your public key
						email: "tapan@247commerce.co.uk", //document.getElementById("email-address").value,
						amount: 1999, //document.getElementById("amount").value * 100,
						firstname: "Tapan",//document.getElementById("first-name").value,
						lastname: "Basak", //document.getElementById("last-name").value,
						ref: ""+Math.floor((Math.random() * 1000000000) + 1), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
						// label: "Optional string that replaces customer email"
						onClose: function(){
							alert("Window closed.");
						},
						callback: function(response){
						let message = "Payment complete! Reference: " + response.reference;
							alert(message);
						}
					});
					handler.openIframe();
				}

				$(document).ready(function() {
					console.log("document loaded");    
					
					var stIntId = setInterval(function() {
						if($("li.checkout-step--payment")[0]) {

							$("li.checkout-step--payment").find("a").append(\'<form id="paymentForm" name="paystack"><button type="submit" onclick="payWithPaystack()" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With Paystack</button></form>\');
							
							const paymentForm = document.getElementById("paymentForm");
							paymentForm.addEventListener("submit", payWithPaystack, false);
							clearInterval(stIntId);
						}
					}, 2000);
				});';
		$filename = 'custom_script.js';
		saveFile($filename,$filecontent,$folderPath);
	}
}
?>