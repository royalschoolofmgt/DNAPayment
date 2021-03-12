<?php
/**
	* Alter Client Details Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

//print_r($_REQUEST);exit;
if(isset($_REQUEST['container_id'])){
	$conn = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	if(!empty($email_id)){
		$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email_id."'");
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if (count($result) > 0) {
			$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id='".$email_id."'");
			$stmt_c->execute();
			$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
			$result_c = $stmt_c->fetchAll();
			$enable = 0;
			if(isset($_REQUEST['is_enabled']) && $_REQUEST['is_enabled'] == "on"){
				$enable = 1;
			}
			if (count($result_c) > 0) {
				$usql = 'update custom_dnapay_button set container_id="'.$_REQUEST['container_id'].'",css_prop="'.$_REQUEST['css_prop'].'",is_enabled="'.$enable.'" where email_id="'.$email_id.'"';
				// execute the query
				$conn->exec($usql);
				$sellerdb = $result[0]['sellerdb'];
				alterFile($sellerdb,$email_id);
			}else{
				$isql = 'insert into custom_dnapay_button(email_id,container_id,css_prop,is_enabled) values("'.$email_id.'","'.$_REQUEST['container_id'].'","'.$_REQUEST['css_prop'].'","'.$enable.'")';
				$stmt_i = $conn->prepare($isql);
				// execute the query
				$stmt_i->execute();
				$sellerdb = $result[0]['sellerdb'];
				alterFile($sellerdb,$email_id);
			}
			header("Location:customButton.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}else{
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']);
	}
}else{
	header("Location:customButton.php?bc_email_id=".@$_REQUEST['bc_email_id']);
}

/* creating tables Based on Seller */
function alterFile($sellerdb,$email_id){
	$conn = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		
		$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id='".$email_id."'");
		$stmt_c->execute();
		$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
		$result_c = $stmt_c->fetchAll();
		if (count($result_c) > 0) {
			$result_c = $result_c[0];
		}
		$enable = 0;
		if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == 1){
			$enable = 1;
		}
		
		$filecontent = '';
		if($enable == 1){
			$id = $result_c['container_id'];
			$css_prop = $result_c['css_prop'];
			if(!empty($id)){
				$filecontent = '$(document).ready(function() {
			var stIntId = setInterval(function() {
				if($(".checkout-step--payment").length > 0) {
					if($("#247dnapayment").length == 0){
						$("'.$id.'").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($email_id)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DNA Payments</button></form></div>\');
						clearInterval(stIntId);
					}
				}
			}, 1000);';
			}else{
				$filecontent = '$(document).ready(function() {
		var stIntId = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247dnapayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($email_id)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DNA Payments</button></form></div>\');
					clearInterval(stIntId);
				}
			}
		}, 1000);';
			}
			
			if(!empty($css_prop)){
				$filecontent .= '$("body").append("<style>'.preg_replace("/[\r\n]*/","",$css_prop).'</style>");';
			}
		}else{
				$filecontent = '$(document).ready(function() {
		var stIntId = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247dnapayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($email_id)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DNA Payments</button></form></div>\');
					clearInterval(stIntId);
				}
			}
		}, 1000);';
		}
	$filecontent .= '$("body").on("click","#dnapaymentForm",function(e){
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