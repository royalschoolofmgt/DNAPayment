<?php
/**
	* Token Validation Page
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
				$data = createFolder($sellerdb);
				$sql = 'update dna_token_validation set client_id="'.$_REQUEST['client_id'].'",client_secret="'.$_REQUEST['client_secret'].'",client_terminal_id="'.$_REQUEST['client_terminal_id'].'" where email_id="'.$email_id.'"';
				$con->query($sql);
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']);
			}else{
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
			}
		}else{
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}else{
	header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
}
/* creating folder Based on Seller */
function createFolder($sellerdb){
	$con = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		$filecontent = '$(document).ready(function() {
	var stIntId = setInterval(function() {
		if($("li.checkout-step--payment")[0]) {
			$("li.checkout-step--payment").find("a").append(\'<form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="InZpbGFzQDI0N2NvbW1lcmNlLmNvLnVrIg==" ><button type="submit" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With DNA</button></form>\');
			clearInterval(stIntId);
		}
	}, 2000);
	$("body").on("click","#dnapaymentForm",function(e){
		e.preventDefault();
		var key = $("body #247dnakey").val();
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "/api/storefront/cart",
			success: function (res) {
				console.log(res,"ressssssssss");
				if(res.length > 0){
					var cartData = res[0]["lineItems"]["physicalItems"];
					var totalAmount = 0;
					var currency = res[0]["currency"]["code"];
					$.each(cartData,function(k,v){
						var quan = v.quantity;
						var total = (parseFloat(quan) * parseFloat(v.salePrice));
						totalAmount += parseFloat(total);
					});
					if(parseFloat(totalAmount) > 0){
						$.ajax({
							type: "POST",
							dataType: "json",
							url: "https://bigcommerce.247commerce.co.uk/dna_payment/authentication.php",
							dataType: "json",
							data:{"authKey":key,"totalAmount":totalAmount,"currency":currency},
							success: function (res) {
								if(res.status){
									var data = JSON.parse(window.atob(res.data));
									window.DNAPayments.openPaymentWidget(data);
								}
							}
						});
					}
				}
			}
		});
	});
});';
		$filename = 'custom_script.js';
		$res = saveFile($filename,$filecontent,$folderPath);
	}
}
?>