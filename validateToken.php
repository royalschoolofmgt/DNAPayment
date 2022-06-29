<?php
/**
	* Token Validation Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('config.php');
require_once('db-config.php');
require_once('helper.php');

/*require 'log-autoloader.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;*/

if(isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && isset($_REQUEST['client_terminal_id'])){
	$conn = getConnection();
	$email_id = @$_REQUEST['bc_email_id'];
	$key = @$_REQUEST['key'];
	if(!empty($email_id) && !empty($key)){
		$validation_id = json_decode(base64_decode($_REQUEST['key']),true);
		$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
		$stmt->execute([$email_id,$validation_id]);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		//print_r($result[0]);exit;
		if (isset($result[0])) {
			$result = $result[0];
			if(!empty($_REQUEST['client_id']) && !empty($_REQUEST['client_secret']) && !empty($_REQUEST['client_terminal_id'])){
				$valid = validateToken($email_id,$_REQUEST['client_id'],$_REQUEST['client_secret'],$_REQUEST['client_terminal_id'],$validation_id);
				if($valid){
					$sellerdb = $result['sellerdb'];
					$data = createFolder($sellerdb,$email_id,$validation_id);
					$sql = 'update dna_token_validation set client_id=?,client_secret=?,client_terminal_id=? where email_id=? and validation_id=?';
					//echo $sql;exit;
					$stmt = $conn->prepare($sql);
					$stmt->execute([$_REQUEST['client_id'],$_REQUEST['client_secret'],$_REQUEST['client_terminal_id'],$email_id,$validation_id]);
					header("Location:dashboard.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
				}else{
					header("Location:index.php?error=1&bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
				}
			}else{
				header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
			}
		}else{
			header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
		}
	}else{
		header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
	}
}else{
	header("Location:index.php?bc_email_id=".@$_REQUEST['bc_email_id']."&key=".@$_REQUEST['key']);
}

/* Validating Token */
function ValidateToken($email_id,$client_id,$client_secret,$client_terminal_id,$validation_id){
	$conn = getConnection();
	$response = false;
	if(!empty($client_id) && !empty($client_secret) && !empty($client_terminal_id)){
		$header = array(
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
						"scope"=>"webapi",
						"client_id"=>$client_id,
						"client_secret"=>$client_secret,
						"grant_type"=>"client_credentials",
					);
		
		$url = AUTHENTICATE_URL;
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);   
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		
		$res = curl_exec($ch);
		curl_close($ch);
		
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response,token_validation_id) values(?,?,?,?,?,?,?)';
				
		$stmt = $conn->prepare($log_sql);
		$stmt->execute([$email_id,"DNA","Validation",addslashes($url),addslashes($request),addslashes($res),$validation_id]);
		
		if(!empty($res)){
			$data = json_decode($res,true);
			if(isset($data['access_token'])){
				$response = true;
			}
		}
	}
	return $response;
}

/* creating folder Based on Seller */
function createFolder($sellerdb,$email_id,$validation_id){
	$conn = getConnection();
	$tokenData = array("email_id"=>$email_id,"key"=>$validation_id);
	if(!empty($sellerdb)){
		$folderPath = './'.$sellerdb;
		$filecontent = '$("head").append("<script src=\"'.BASE_URL.'js/247loader.js\" ></script>");';
		$filecontent .= '$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'.BASE_URL.'css/247loader.css\" />");';
		if(ENVIRONMENT == "dev"){
			$filecontent .= 'window.DNAPayments.configure ({                 
				isTestMode: true
			});';//Remove for Live
		}
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
	}, 1000);
	$("body").on("click","button[data-test=\'step-edit-button\'], button[data-test=\'sign-out-link\']",function(e){
		//hide dna payment button
		$("#247dnapayment").hide();
	});

	$("body").on("click", "button#checkout-customer-continue, button#checkout-shipping-continue, button#checkout-billing-continue", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
	});
	$("body").on("click","#dnapaymentForm",function(e){
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