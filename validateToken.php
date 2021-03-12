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
	$conn = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	if(!empty($email_id)){
		$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		//print_r($result[0]);exit;
		if (isset($result[0])) {
			$result = $result[0];
			if(!empty($_REQUEST['client_id']) && !empty($_REQUEST['client_secret']) && !empty($_REQUEST['client_terminal_id'])){
				$sellerdb = $result['sellerdb'];
				$data = createFolder($sellerdb,$email_id);
				$sql = 'update dna_token_validation set client_id="'.$_REQUEST['client_id'].'",client_secret="'.$_REQUEST['client_secret'].'",client_terminal_id="'.$_REQUEST['client_terminal_id'].'" where email_id="'.$email_id.'"';
				//echo $sql;exit;
				$stmt = $conn->prepare($sql);
				$stmt->execute();
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
function createFolder($sellerdb,$email_id){
	$conn = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		$filecontent = '$(document).ready(function() {
	var stIntId = setInterval(function() {
		if($(".checkout-step--payment").length > 0) {
			if($("#247dnapayment").length == 0){
				$(".checkout-step--payment .checkout-view-header").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($email_id)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DNA Payments</button></form></div>\');
				clearInterval(stIntId);
			}
		}
	}, 1000);
	$("body").on("click","#dnapaymentForm",function(e){
		e.preventDefault();
		var buttonlength = $(".button--tertiary").length;
		if(buttonlength >= 3){
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
											url: "'.BASE_URL.'authentication.php",
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
		}else{
			alert("Please Select Billing Address and Shipping Address");
		}
	});
});';
		$filename = 'custom_script.js';
		$res = saveFile($filename,$filecontent,$folderPath);
	}
}
?>