var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};
var authKey = getUrlParameter('authKey');
if(authKey != ""){
	var url = 'https://dnapayments.247commerce.co.uk/getOrderDetails.php?authKey='+authKey;
	$.ajax({
		type: "GET",
		dataType: "json",
		url: url,
		success: function (res) {
			if(res.status){
				var data = res.data;
				var bilingAddress = data.billing_address;
				var storeData = res.data['storeData'];
				var productsData = res.data['productsData'];
				var orderItems = productsData.length;
				var ItemsCount = "1 Item";
				if(parseInt(orderItems) > 1){
					ItemsCount = orderItems+" Items";
				}
				var html = '<div class="container">';
					html += '<header class="checkoutHeader optimizedCheckout-header">';
						html += '<div class="checkoutHeader-content">';
							html += '<h1 class="is-srOnly">Checkout</h1>';
							html += '<h2 class="checkoutHeader-heading">';
								html += '<a class="checkoutHeader-link" href="'+storeData.secure_url+'">';
									html += '<img alt="247Commerce" class="checkoutHeader-logo" id="logoImage" src="'+storeData.logo['url']+'">';
								html += '</a>';
							html += '</h2>';
						html += '</div>';
					html += '</header>';
					html += '<div id="checkout-app">';
						html += '<div class="layout optimizedCheckout-contentPrimary">';
							html += '<div class="layout-main">';
								html += '<div class="orderConfirmation">';
									html += '<h1 class="optimizedCheckout-headingPrimary" data-test="order-confirmation-heading">Thank you '+bilingAddress.first_name+'!</span></h1>';
									html += '<section class="orderConfirmation-section">';
										html += '<p data-test="order-confirmation-order-number-text"><span>Your order number is <strong>'+data.id+'</strong></span></p>';
										html += '<p data-test="order-confirmation-order-status-text">';
											html += '<span>An email will be sent containing information about your purchase. If you have any questions about your purchase, email us at <a target="_top" href="mailto:'+storeData.admin_email+'?Subject=Order '+data.id+'">'+storeData.admin_email+'</a> or call us at <a href="tel://'+storeData.phone+'">'+storeData.phone+'</a>.</span>';
										html += '</p>';
									html += '</section>';
									html += '<div class="continueButtonContainer">';
										html += '<a href="'+storeData.secure_url+'" target="_top">';
											html += '<button class="button button--tertiary optimizedCheckout-buttonSecondary" type="button">Continue Shopping »</button>';
										html += '</a>';
									html += '</div>';
								html += '</div>';
							html += '</div>';
							html += '<aside class="layout-cart">';
								html += '<article class="cart optimizedCheckout-orderSummary" data-test="cart">';
									html += '<header class="cart-header">';
										html += '<h3 class="cart-title optimizedCheckout-headingSecondary">Order Summary</h3>';
										html += '<a class="cart-header-link" id="cart-print-link" onclick="window.print()" >';
											html += '<div class="icon">';
												html += '<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"></path></svg>';
											html += '</div> Print';
										html += '</a>';
									html += '</header>';
									html += '<section class="cart-section optimizedCheckout-orderSummary-cartSection">';
										html += '<h3 class="cart-section-heading optimizedCheckout-contentPrimary" data-test="cart-count-total">'+ItemsCount+'</h3>';
										html += '<ul aria-live="polite" class="productList">';
											$.each(productsData,function(k,v){
												var thumbnail = "https://dnapayments.247commerce.co.uk/images/ProductDefault.png";
												if(v.productImages.length > 0){
													if(typeof(v.productImages[0]['url_thumbnail']) != "undefined"){
														thumbnail = v.productImages[0]['url_thumbnail'];
													}
												}
												html += '<li class="productList-item is-visible">';
													html += '<div class="product" data-test="cart-item">';
														html += '<figure class="product-column product-figure">';
															html += '<img alt="'+v.name+'" data-test="cart-item-image" src="'+thumbnail+'">';
														html += '</figure>';
														html += '<div class="product-column product-body">';
															html += '<h5 class="product-title optimizedCheckout-contentPrimary" data-test="cart-item-product-title">'+v.quantity+' x '+v.name+'</h5>';
															html += '<ul class="product-options optimizedCheckout-contentSecondary" data-test="cart-item-product-options"></ul>';
														html += '</div>';
														html += '<div class="product-column product-actions">';
															html += '<div class="product-price optimizedCheckout-contentPrimary" data-test="cart-item-product-price">'+data.currency_code+''+parseFloat(v.total_inc_tax).toFixed(2)+'</div>';
														html += '</div>';
													html += '</div>';
												html += '</li>';
											});
										html += '</ul>';
									html += '</section>';
									html += '<section class="cart-section optimizedCheckout-orderSummary-cartSection">';
										html += '<div data-test="cart-subtotal">';
											html += '<div aria-live="polite" class="cart-priceItem optimizedCheckout-contentPrimary cart-priceItem--subtotal">';
												html += '<span class="cart-priceItem-label"><span data-test="cart-price-label">Subtotal  </span></span>';
												html += '<span class="cart-priceItem-value"><span data-test="cart-price-value">'+data.currency_code+''+parseFloat(data.subtotal_inc_tax).toFixed(2)+'</span></span>';
											html += '</div>';
										html += '</div>';
										var ShippingText = "Free";
										if(parseFloat(data.shipping_cost_inc_tax) > 0){
											ShippingText = data.currency_code+''+parseFloat(data.discount_amount).toFixed(2);
										}
										html += '<div data-test="cart-shipping">';
											html += '<div aria-live="polite" class="cart-priceItem optimizedCheckout-contentPrimary">';
												html += '<span class="cart-priceItem-label"><span data-test="cart-price-label">Shipping  </span></span>';
												html += '<span class="cart-priceItem-value"><span data-test="cart-price-value">'+ShippingText+'</span></span>';
											html += '</div>';
										html += '</div>';
										if(parseFloat(data.discount_amount) > 0){
											html += '<div data-test="cart-shipping">';
												html += '<div aria-live="polite" class="cart-priceItem optimizedCheckout-contentPrimary">';
													html += '<span class="cart-priceItem-label"><span data-test="cart-price-label">Discount Amount  </span></span>';
													html += '<span class="cart-priceItem-value"><span data-test="cart-price-value">'+data.currency_code+''+parseFloat(data.discount_amount).toFixed(2)+'</span></span>';
												html += '</div>';
											html += '</div>';
										}
										html += '<div data-test="cart-taxes">';
											html += '<div aria-live="polite" class="cart-priceItem optimizedCheckout-contentPrimary">';
												html += '<span class="cart-priceItem-label"><span data-test="cart-price-label">VAT  </span></span>';
												html += '<span class="cart-priceItem-value"><span data-test="cart-price-value">'+data.currency_code+''+parseFloat(data.total_inc_tax).toFixed(2)+'</span></span>';
											html += '</div>';
										html += '</div>';
									html += '</section>';
									html += '<section class="cart-section optimizedCheckout-orderSummary-cartSection">';
										html += '<div data-test="cart-total">';
											html += '<div aria-live="polite" class="cart-priceItem optimizedCheckout-contentPrimary cart-priceItem--total">';
												html += '<span class="cart-priceItem-label"><span data-test="cart-price-label">Total ('+data.currency_code+')  </span></span>';
												html += '<span class="cart-priceItem-value"><span data-test="cart-price-value">'+data.currency_code+''+parseFloat(data.total_inc_tax).toFixed(2)+'</span></span>';
											html += '</div>';
										html += '</div>';
									html += '</section>';
								html += '</article>';
							html += '</aside>';
						html += '</div>';
					html += '</div>';
				html += '</div>';
				$('body').html(html);
			}
		}
	});
}
$('body').on('click','#cart-print-link',function(){
	alert("print");
	window.print();
});