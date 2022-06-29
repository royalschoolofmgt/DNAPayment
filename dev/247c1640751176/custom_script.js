$("head").append("<script src=\"https://dnapayments.247commerce.co.uk/dev/js/247loader.js\" ></script>");$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"https://dnapayments.247commerce.co.uk/dev/css/247loader.css\" />");window.DNAPayments.configure ({                 
				isTestMode: true
			});$(document).ready(function() {
		var stIntId = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247dnapayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after('<div id="247dnapayment" class="checkout-form" style="padding:1px;display:none;"><div id="247Err" style="color:red"></div><li class=\"form-checklist-item optimizedCheckout-form-checklist-item\"><div class=\"form-checklist-header\" style=\"background:#F9F9F9;\"><div class=\"form-field\"><input id=\"radio-dna-pay\" name=\"dnaPayments\" class=\"form-checklist-checkbox optimizedCheckout-form-checklist-checkbox\" type=\"radio\" value=\"dnapay\" checked><label for=\"radio-dna-pay\" class=\"form-label optimizedCheckout-form-label\"><span class=\"paymentProviderHeader-name\" data-test=\"payment-method-name\"><span class=\"paymentProviderHeader-name\" data-test=\"payment-method-name\">DNA Payments</span></span></label></div></div></li><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="eyJlbWFpbF9pZCI6InNha2lkbmFAMjQ3Y29tbWVyY2UuY28udWsiLCJrZXkiOiIyIn0=" ><button type="submit" id="pay-button-dna" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">DEBIT/CREDIT CARDS | powered by DNA PAYMENTS</button></form></div>');
					loadStatus();
					clearInterval(stIntId);
					/**
						when user is logged in and billing/shipping 
						address set show custom payment button 
					*/
					checkDnaPayBtnVisibility();
				}
			}
		}, 1000);$("body").on("click","button[data-test='step-edit-button'], button[data-test='sign-out-link']",function(e){
		//hide dna payment button
		$("#247dnapayment").hide();
	});

	$("body").on("click", "button#checkout-customer-continue, button#checkout-shipping-continue, button#checkout-billing-continue", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
		$("body input[name=\"paymentProviderRadio\"]").trigger("click");
	});
	$("body").on("click", "#useStoreCredit", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
		$("body input[name=\"paymentProviderRadio\"]").trigger("click");
	});
	$("body").on("click", "#applyRedeemableButton", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
		$("body input[name=\"paymentProviderRadio\"]").trigger("click");
	});
	$("body").on("click", ".cart-priceItem-link", function() {
		setTimeout(checkDnaPayBtnVisibility, 2000);
		$("body input[name=\"paymentProviderRadio\"]").trigger("click");
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
			source: "https://dnapayments.247commerce.co.uk/dev/images/img.svg",
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
								url: "/api/storefront/checkouts/"+cartId+"?include=cart.lineItems.physicalItems.options%2Ccart.lineItems.digitalItems.options%2Ccustomer%2Ccustomer.customerGroup%2Cpayments%2Cpromotions.banners%2Ccart.lineItems.physicalItems.categoryNames%2Ccart.lineItems.digitalItems.categoryNames",
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
									var grandTotal = parseFloat(cartres.grandTotal);
									var StoreCreditAmount = 0;
									var totalCartPrice = 0;
									var isStoreCreditApplied = cartres.isStoreCreditApplied;
									if (parseInt(cartres.cart["customerId"]) > 0) {
										if (isStoreCreditApplied == true) {
											StoreCreditAmount = parseFloat(cartres.customer["storeCredit"]);
										}
									}
									if (StoreCreditAmount > 0) {
										if (grandTotal > StoreCreditAmount) {
											totalCartPrice = parseFloat(parseFloat(grandTotal) - parseFloat(StoreCreditAmount));
										}
									} else {
										totalCartPrice = grandTotal;
									}
									if(bstatus ==0 && sstatus == 0 && parseFloat(totalCartPrice)>0){
										$.ajax({
											type: "POST",
											dataType: "json",
											crossDomain: true,
											url: "https://dnapayments.247commerce.co.uk/dev/authentication.php",
											dataType: "json",
											data:{"authKey":key,"cartId":cartId,isStoreCreditApplied: isStoreCreditApplied},
											success: function (res) {
												if(res.status){
													if(res.card_token){
														var card_token_data = res.card_token_data;
														window.DNAPayments.configure ({
															cards:card_token_data
														});
														var data = JSON.parse(window.atob(res.data));
														window.DNAPayments.openPaymentIframeWidget(data);
													}else{
														var data = JSON.parse(window.atob(res.data));
														window.DNAPayments.openPaymentIframeWidget(data);
													}
												}else{
													alert("Something went wrong");
													$("#247dnapayment").waitMe("hide");
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
			url: "https://dnapayments.247commerce.co.uk/dev/getPaymentStatus.php",
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
							url: "/api/storefront/checkouts/"+cartId+"?include=cart.lineItems.physicalItems.options%2Ccart.lineItems.digitalItems.options%2Ccustomer%2Ccustomer.customerGroup%2Cpayments%2Cpromotions.banners%2Ccart.lineItems.physicalItems.categoryNames%2Ccart.lineItems.digitalItems.categoryNames",
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

								var grandTotal = parseFloat(cartres.grandTotal);
								var StoreCreditAmount = 0;
								var totalCartPrice = 0;
								var isStoreCreditApplied = cartres.isStoreCreditApplied;
								if (parseInt(cartres.cart["customerId"]) > 0) {
									if (isStoreCreditApplied == true) {
										StoreCreditAmount = parseFloat(cartres.customer["storeCredit"]);
									}
								}
								if (StoreCreditAmount > 0) {
									if (grandTotal > StoreCreditAmount) {
										totalCartPrice = parseFloat(parseFloat(grandTotal) - parseFloat(StoreCreditAmount));
									}
								} else {
									totalCartPrice = grandTotal;
								}
								if(bstatus ==0 && sstatus == 0 && parseFloat(totalCartPrice)>0){
									//hide cardstream payment button
									dnaPayButtonControl("radio-dna-pay", "pay-button-dna", "dnaPaymentForm");
									$("#247dnapayment").show();
								}else{
									$("#247dnapayment").hide();
								}
							}
						});
					}
				}
			}
		}

	});
}
var dnaPayButtonControl = function(customRadIDCustomRadioID, customPayButtonID, customFormID) {
    var payRevBtnIntId = setInterval(function() {
        if ($(".checkout-step--payment").length > 0) {
            if ($("#checkout-payment-continue").length > 0 && $(".loadingNotification").length == 0 && $("#" + customRadIDCustomRadioID).length > 0 && $("#" + customRadIDCustomRadioID).prop("checked")) {

                $('input[name="paymentProviderRadio"]').each(function(i) {
                    $(this).prop("checked", false);
                });

                $(".form-checklist-body").hide(100);
                $("#checkout-payment-continue").attr("disabled", "disabled");
                $("#checkout-payment-continue").hide();
                clearInterval(payRevBtnIntId);

                //attach click event with custom radio btn & default radio btn container
                $("#" + customRadIDCustomRadioID).on("click", function() {

                    //unchecked default radio button
                    $('input[name="paymentProviderRadio"]').each(function(i) {
                        $(this).prop("checked", false);
                    });

                    //disable default place order button
                    $(".form-checklist-body").hide(500);
                    $("#checkout-payment-continue").attr("disabled", "disabled");
                    $("#checkout-payment-continue").hide();

                    //enable custom place order button
                    $("#" + customFormID).show(500);
                    $("#" + customPayButtonID).removeAttr("disabled");
                    $("#" + customPayButtonID).css("opacity", "1");

                });

                $('input[name="paymentProviderRadio"]').on("click", function() {

                    //uncheck custom radio button
                    $("#" + customRadIDCustomRadioID).prop("checked", false);

                    //disable custom place order button
                    $("#" + customFormID).hide(500);
                    $("#" + customPayButtonID).attr("disabled", "disabled");
                    $("#" + customPayButtonID).css("opacity", ".5");

                    //enable default place order button
                    $(".form-checklist-body").show(500);
                    $("#checkout-payment-continue").removeAttr("disabled");
                    $("#checkout-payment-continue").show();
                });


            }
        }
    }, 1000);
}