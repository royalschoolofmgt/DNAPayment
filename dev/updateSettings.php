<?php
/**
	* Alter Client Details Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

if(isset($_REQUEST['payment_option'])){
	$conn = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	$key = @$_REQUEST['key'];
	if(!empty($email_id) && !empty($key)){
		$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
	$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if (count($result) > 0) {
			if(!empty($_REQUEST['payment_option'])){
				$result = $result[0];
				$sellerdb = $result['sellerdb'];
				//alterFile($sellerdb,$email_id);
				
				$sql = 'update dna_token_validation set payment_option=? where email_id=? and validation_id=?';
				// Prepare statement
				$stmt = $conn->prepare($sql);
				// execute the query
				$stmt->execute([$_REQUEST['payment_option'],$email_id,$validation_id,]);
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&updated=1");
			}else{
				header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
			
		}else{
			header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
		}
	}else{
		header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}else{
	header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
}
/* creating tables Based on Seller */
function alterFile($sellerdb,$email_id){
	$conn = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		$filecontent = '$(document).ready(function() {
	function callInterval(){
		var stIntId = setInterval(function() {
			if($("#checkout-payment-continue").length > 0) {
				if($(".247dnapayment").length == 0){
					$("#checkout-payment-continue").before(\'<div class="247dnapayment" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($email_id)).'" ><button type="submit" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With DNA</button></form></div>\');
				}
				clearInterval(stIntId);
			}
		}, 1000);
	}
	var stIntId1 = setInterval(function() {
		if($("#checkout-payment-continue").length == 0) {
			callInterval();
		}
	}, 1000);
	callInterval();
	$("body").on("click","#dnapaymentForm",function(e){
		e.preventDefault();
		var key = $("body #247dnakey").val();
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "/api/storefront/cart",
			success: function (res) {
				if(res.length > 0){
					if(res[0]["id"] != undefined){
						var cartId = res[0]["id"];
						if(cartId != ""){
							$.ajax({
								type: "GET",
								dataType: "json",
								url: "/api/storefront/checkouts/"+cartId,
								success: function (cartres) {
									var cartData = window.btoa(JSON.stringify(cartres));
									$.ajax({
										type: "POST",
										dataType: "json",
										crossDomain: true,
										url: "https://dnapayments.247commerce.co.uk/authentication.php",
										dataType: "json",
										data:{"authKey":key,"cartId":cartId,cartData:cartData},
										success: function (res) {
											if(res.status){
												var data = JSON.parse(window.atob(res.data));
												window.DNAPayments.openPaymentWidget(data);
											}
										}
									});
								}
							});
						}
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