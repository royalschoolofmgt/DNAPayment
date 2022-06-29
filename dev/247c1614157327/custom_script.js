$(document).ready(function() {
			console.log("document loaded");
			var stIntId = setInterval(function() {
				if($("li.checkout-step--payment")[0]) {
					$("li.checkout-step--payment").find("a").append('<form id="dnapaymentForm" name="dnapaymet"><button type="submit" onclick="payWithPaystack()" class="" style="background-color: #424242;border-color: #424242;color: #fff;">Pay With DNA</button></form>');
					clearInterval(stIntId);
				}
			}, 2000);
		});