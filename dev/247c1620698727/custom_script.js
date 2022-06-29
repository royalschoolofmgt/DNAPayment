$("head").append("<script src=\"https://dnapayments.247commerce.co.uk/js/247loader.js\" ></script>");$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"https://dnapayments.247commerce.co.uk/css/247loader.css\" />");window.DNAPayments.configure ({                 
			isTestMode: true
		});$(document).ready(function() {
			var stIntId = setInterval(function() {
				if($(".checkout-step--payment").length > 0) {
					if($("#247dnapayment").length == 0){
						$(".checkout-step--payment .checkout-view-header").after('<div id="247dnapayment" class="checkout-form" style="padding:1px;display:none;"><div id="247Err" style="color:red"></div><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="eyJlbWFpbF9pZCI6ImNvbGluQGRuYXBheW1lbnRzLmNvbSIsImtleSI6IjEifQ==" ><button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DEBIT/CREDIT CARDS | powered by DNA PAYMENTS</button></form></div>');
						loadStatus();
						clearInterval(stIntId);
						/**
							when user is logged in and billing/shipping 
							address set show custom payment button 
						*/
						checkDnaPayBtnVisibility();
					}
				}
			}, 1000);$("body").append("<style>#dnapaymentForm>button{	display:block;	background-color: #00FF00 !important;	color: #000000 !important;	border-color: #FF0000 !important;}</style>");$("body").on("click","button[data-test='step-edit-button'], button[data-test='sign-out-link']",function(e){
		//hide dna payment button
		$("#247dnapayment").hide();
	});

	$("body").on("click", "button#checkout-customer-continue, button#checkout-shipping-continue, button#checkout-billing-continue", function() {
		checkDnaPayBtnVisibility();
	});$("body").on("click","#dnapaymentForm",function(e){
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
			source: "https://dnapayments.247commerce.co.uk/images/img.svg",
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
											url: "https://dnapayments.247commerce.co.uk/authentication.php",
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
	/*if(typeof(billingAddress.stateOrProvince) != "undefined" && billingAddress.stateOrProvince !== null && billingAddress.stateOrProvince !== "") {
		
	}else{
		errorCount++;
	}*/
	if(typeof(billingAddress.postalCode) != "undefined" && billingAddress.postalCode !== null && billingAddress.postalCode !== "") {
		
	}else{
		errorCount++;
	}
	if(typeof(billingAddress.country) != "undefined" && billingAddress.country !== null && billingAddress.country !== "") {
		
	}else{
		errorCount++;
	}
	/*if(typeof(billingAddress.phone) != "undefined" && billingAddress.phone !== null && billingAddress.phone !== "") {
		
	}else{
		errorCount++;
	}*/
	
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
			if(typeof(shippingAddress.stateOrProvince) != "undefined" && shippingAddress.stateOrProvince !== null && shippingAddress.stateOrProvince !== "") {
				
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
			if(typeof(shippingAddress.phone) != "undefined" && shippingAddress.phone !== null && shippingAddress.phone !== "") {
				
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
			url: "https://dnapayments.247commerce.co.uk/getPaymentStatus.php",
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
function checkDnaPayBtnVisibility() {
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
}