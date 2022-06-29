$(document).ready(function() {
	function callInterval(){
		var stIntId = setInterval(function() {
			if($("#checkout-payment-continue").length > 0) {
				if($(".247dnapayment").length == 0){
					$("#checkout-payment-continue").before('<div class="247dnapayment" style="padding:1px"><form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="ImJpZ2lAMjQ3Y29tbWVyY2UuY28udWsi" ><button type="submit" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With DNA</button></form></div>');
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
							url: "https://dnapayments.247commerce.co.uk/authentication.php",
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
});