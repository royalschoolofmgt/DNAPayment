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
	$key = @$_REQUEST['key'];
	if(!empty($email_id) && !empty($key)){
		$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if (count($result) > 0) {
			$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id=? and token_validation_id=?");
			$stmt_c->execute([$email_id,$validation_id]);
			$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
			$result_c = $stmt_c->fetchAll();
			$enable = 0;
			if(isset($_REQUEST['is_enabled']) && $_REQUEST['is_enabled'] == "on"){
				$enable = 1;
			}
			if (count($result_c) > 0) {
				$usql = 'update custom_dnapay_button set container_id=?,css_prop=?,is_enabled=? where email_id=? and token_validation_id=?';
				// execute the query
				$stmt_u = $conn->prepare($usql);
				$stmt_u->execute([$_REQUEST['container_id'],$_REQUEST['css_prop'],$enable,$email_id,$validation_id]);
				$sellerdb = $result[0]['sellerdb'];
				alterFile($sellerdb,$email_id,$validation_id);
			}else{
				$isql = 'insert into custom_dnapay_button(email_id,container_id,css_prop,is_enabled,token_validation_id) values(?,?,?,?,?)';
				$stmt_i = $conn->prepare($isql);
				// execute the query
				$stmt_i->execute([$email_id,$_REQUEST['container_id'],$_REQUEST['css_prop'],$enable,$validation_id]);
				$sellerdb = $result[0]['sellerdb'];
				alterFile($sellerdb,$email_id,$validation_id);
			}
			header("Location:customButton.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']."&updated=1");
		}else{
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}else{
	header("Location:customButton.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
}

/* creating tables Based on Seller */
function alterFile($sellerdb,$email_id,$validation_id){
	$tokenData = array("email_id"=>$email_id,"key"=>$validation_id);
	$conn = getConnection();
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		
		$stmt_c = $conn->prepare("select * from custom_dnapay_button where email_id=? and token_validation_id=?");
		$stmt_c->execute([$email_id,$validation_id]);
		$stmt_c->setFetchMode(PDO::FETCH_ASSOC);
		$result_c = $stmt_c->fetchAll();
		if (count($result_c) > 0) {
			$result_c = $result_c[0];
		}
		$enable = 0;
		if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == 1){
			$enable = 1;
		}
		$filecontent = '$("head").append("<script src=\"'.BASE_URL.'js/247loader.js\" ></script>");';
		$filecontent .= '$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'.BASE_URL.'css/247loader.css\" />");';
		if(ENVIRONMENT == "dev"){
			$filecontent .= 'window.DNAPayments.configure ({                 
				isTestMode: true
			});';//Remove for Live
		}
		if($enable == 1){
			$id = $result_c['container_id'];
			$css_prop = $result_c['css_prop'];
			if(!empty($id)){
				$filecontent .= '$(document).ready(function() {
			var stIntId = setInterval(function() {
				if($(".checkout-step--payment").length > 0) {
					if($("#247dnapayment").length == 0){
						$("'.$id.'").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px;display:none;"><div id="247Err" style="color:red"></div><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($tokenData)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DEBIT/CREDIT CARDS | powered by DNA PAYMENTS</button></form></div>\');
						loadStatus();
						clearInterval(stIntId);
						/**
							when user is logged in and billing/shipping 
							address set show custom payment button 
						*/
						checkDnaPayBtnVisibility();
					}
				}
			}, 1000);';
			}else{
				$filecontent .= '$(document).ready(function() {
		var stIntId = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247dnapayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px;display:none;"><div id="247Err" style="color:red"></div><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($tokenData)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DEBIT/CREDIT CARDS | powered by DNA PAYMENTS</button></form></div>\');
					loadStatus();
					clearInterval(stIntId);
					/**
						when user is logged in and billing/shipping 
						address set show custom payment button 
					*/
					checkDnaPayBtnVisibility();
				}
			}
		}, 1000);';
			}
			
			if(!empty($css_prop)){
				$filecontent .= '$("body").append("<style>'.preg_replace("/[\r\n]*/","",$css_prop).'</style>");';
			}
		}else{
				$filecontent .= '$(document).ready(function() {
		var stIntId = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247dnapayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after(\'<div id="247dnapayment" class="checkout-form" style="padding:1px;display:none;"><div id="247Err" style="color:red"></div><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="'.base64_encode(json_encode($tokenData)).'" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DEBIT/CREDIT CARDS | powered by DNA PAYMENTS</button></form></div>\');
					loadStatus();
					clearInterval(stIntId);
					/**
						when user is logged in and billing/shipping 
						address set show custom payment button 
					*/
					checkDnaPayBtnVisibility();
				}
			}
		}, 1000);';
		}
		$filecontent .= '$("body").on("click","button[data-test=\'step-edit-button\'], button[data-test=\'sign-out-link\']",function(e){
		//hide dna payment button
		$("#247dnapayment").hide();
	});

	$("body").on("click", "button#checkout-customer-continue, button#checkout-shipping-continue, button#checkout-billing-continue", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
	});';
	$filecontent .= '$("body").on("click","#dnapaymentForm",function(e){
		e.preventDefault();
		var text = "Please wait...";
		var current_effect = "bounce";
		var key = $("body #247dnakey").val();
		$("#247dnapayment").waitMe({
			effect: current_effect,
			text: text,
			bg: "rgba(255,255,255,0.7)",
			color: "#000",
			maxSize: "",
			waitTime: -1,
			source: "'.BASE_URL.'images/img.svg",
			textPos: "vertical",
			fontSize: "",
			onClose: function(el) {}
		});
		var checkDownlProd = false;
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "/api/storefront/cart",
			success: function (res) {
				if(res.length > 0){
					if(res[0]["id"] != undefined){
						var cartId = res[0]["id"];
						var cartCheck = res[0]["lineItems"];
						checkDownlProd = checkOnlyDownloadableProducts(cartCheck);
						if(cartId != ""){
							$.ajax({
								type: "GET",
								dataType: "json",
								url: "/api/storefront/checkouts/"+cartId,
								success: function (cartres) {
									var billingAddress = "";
									var consignments = "";
									var bstatus = 0;
									var sstatus = 0;
									if(typeof(cartres.billingAddress) != "undefined" && cartres.billingAddress !== null) {
										billingAddress = cartres.billingAddress;
										bstatus = billingAddressValdation(billingAddress);
									}
									if(checkDownlProd){
										if(typeof(cartres.consignments) != "undefined" && cartres.consignments !== null) {
											consignments = cartres.consignments;
											sstatus = shippingAddressValdation(consignments);
										}
									}
									if(bstatus ==0 && sstatus == 0 && parseFloat(cartres.grandTotal)>0){
										$.ajax({
											type: "POST",
											dataType: "json",
											crossDomain: true,
											url: "'.BASE_URL.'authentication.php",
											dataType: "json",
											data:{"authKey":key,"cartId":cartId},
											success: function (res) {
												$("#247dnapayment").waitMe("hide");
												if(res.status){
													var data = JSON.parse(window.atob(res.data));
													window.DNAPayments.openPaymentIframeWidget(data);
												}
											},error: function(){
												$("#247dnapayment").waitMe("hide");
											}
										});
									}else{
										alert("Please Select Billing Address and Shipping Address");
										$("#247dnapayment").waitMe("hide");
									}
								},error: function(){
									$("#247dnapayment").waitMe("hide");
								}
							});
						}
					}
				}
			},error: function(){
				$("#dnapaymentForm").waitMe("hide");
			}
		});
		
	});
});
function billingAddressValdation(billingAddress){
	var errorCount = 0;
	if(typeof(billingAddress.firstName) != "undefined" && billingAddress.firstName !== null && billingAddress.firstName !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.lastName) != "undefined" && billingAddress.lastName !== null && billingAddress.lastName !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.address1) != "undefined" && billingAddress.address1 !== null && billingAddress.address1 !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.email) != "undefined" && billingAddress.email !== null && billingAddress.email !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.city) != "undefined" && billingAddress.city !== null && billingAddress.city !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.postalCode) != "undefined" && billingAddress.postalCode !== null && billingAddress.postalCode !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.country) != "undefined" && billingAddress.country !== null && billingAddress.country !== "") {
		
	}else{
		errorCount++;
	}
	
	return errorCount;
}

function shippingAddressValdation(shippingAddress){
	var errorCount = 0;
	if(shippingAddress.length > 0){
		if(typeof(shippingAddress[0].shippingAddress) != "undefined" && shippingAddress[0].shippingAddress !== null && shippingAddress[0].shippingAddress !== "") {
			shippingAddress = shippingAddress[0].shippingAddress;
			if(typeof(shippingAddress.firstName) != "undefined" && shippingAddress.firstName !== null && shippingAddress.firstName !== "") {
				
			}else{
				errorCount++;
			}
			if(typeof(shippingAddress.lastName) != "undefined" && shippingAddress.lastName !== null && shippingAddress.lastName !== "") {
				
			}else{
				errorCount++;
			}
			if(typeof(shippingAddress.address1) != "undefined" && shippingAddress.address1 !== null && shippingAddress.address1 !== "") {
				
			}else{
				errorCount++;
			}
			if(typeof(shippingAddress.city) != "undefined" && shippingAddress.city !== null && shippingAddress.city !== "") {
				
			}else{
				errorCount++;
			}
			if(typeof(shippingAddress.postalCode) != "undefined" && shippingAddress.postalCode !== null && shippingAddress.postalCode !== "") {
				
			}else{
				errorCount++;
			}
			if(typeof(shippingAddress.country) != "undefined" && shippingAddress.country !== null && shippingAddress.country !== "") {
				
			}else{
				errorCount++;
			}
		}
	}else{
		errorCount++;
	}
	return errorCount;
}
function checkOnlyDownloadableProducts(cartData){
	var status = false;
	if(cartData != ""){
		if(cartData.physicalItems.length > 0 || cartData.customItems.length > 0){
			status = true;
		}
		else{
			if(cartData.digitalItems.length > 0){
				status = false;
			}
		}
	}
	return status;
}
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
function loadStatus(){
	var key = getUrlParameter("inv");
	if(key != "undefined" && key != ""){
		$.ajax({
			type: "POST",
			dataType: "json",
			crossDomain: true,
			url: "'.BASE_URL.'getPaymentStatus.php",
			dataType: "json",
			data:{"authKey":key},
			success: function (res) {
				if(res.status){
					$("body #247Err").text(res.msg);
				}
			}
		});
	}
}
';
$filecontent .= 'function checkDnaPayBtnVisibility() {
	var checkDownlProd = false;
	var key = $("body #247dnakey").val();
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "/api/storefront/cart",
		success: function (res) {
			if(res.length > 0){
				if(res[0]["id"] != undefined){
					var cartId = res[0]["id"];
					var cartCheck = res[0]["lineItems"];
					checkDownlProd = checkOnlyDownloadableProducts(cartCheck);
					if(cartId != ""){
						$.ajax({
							type: "GET",
							dataType: "json",
							url: "/api/storefront/checkouts/"+cartId,
							success: function (cartres) {
								var cartData = window.btoa(unescape(encodeURIComponent(JSON.stringify(cartres))));
								var billingAddress = "";
								var consignments = "";
								var bstatus = 0;
								var sstatus = 0;
								if(typeof(cartres.billingAddress) != "undefined" && cartres.billingAddress !== null) {
									billingAddress = cartres.billingAddress;
									bstatus = billingAddressValdation(billingAddress);
								}
								if(checkDownlProd){
									if(typeof(cartres.consignments) != "undefined" && cartres.consignments !== null) {
										consignments = cartres.consignments;
										sstatus = shippingAddressValdation(consignments);
									}
								}

								if(bstatus ==0 && sstatus == 0) {

									//hide cardstream payment button
									$("#247dnapayment").show();
								}
							}
						});
					}
				}
			}
		}

	});
}';
		$filename = 'custom_script.js';
		$res = saveFile($filename,$filecontent,$folderPath);
	}
}
?>