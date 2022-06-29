$(document).ready(function() {
	var stIntId = setInterval(function() {
		if($("li.checkout-step--payment")[0]) {
			$("li.checkout-step--payment").find("a").append('<form id="dnapaymentForm" name="dnapaymet"><input type="hidden" id="247dnakey" value="InZpbGFzQDI0N2NvbW1lcmNlLmNvLnVrIg==" ><button type="submit" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With DNA</button></form>');
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
});