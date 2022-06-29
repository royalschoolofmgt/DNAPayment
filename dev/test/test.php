<?php
	header("Access-Control-Allow-Origin: *");
	$invoice_id = "Test-".time();
	$post_fields = array(
		"scope" => "payment integration_seamless",
        "client_id"  => "bigcommerce",
        "client_secret" => "BRgNFy=vup=*3Zx7M_Q8HP@e!SEc5T_j8?N6A3Ta!e38S!D4uRG2y@htrm5h2tYR",
        "grant_type" => "client_credentials",
        "invoiceId" => $invoice_id,
        "amount" => $_GET['amount'],
        "currency" => "GBP",
        "terminal" => "8911a14f-61a3-4449-a1c1-7a314ee5774c"
	);

	/*echo json_encode($post_fields);
	exit;*/

	$url = "https://test-oauth.dnapayments.com/oauth2/token";
	$ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);   
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);   
    
    if(!($data = curl_exec($ch))) {  
        echo "request-token > Request Curl Error: ".curl_error($ch);                       
    }       
    else { 
    	echo $data;
        $response = json_decode($data, true); 
        print '<pre />';
        print_r($response);
    }  
    curl_close($ch);

    ////exit;	
?>

<html>
  <head>
  </head>
  <body>
    
    <!--<script src="https://test-pay.dnapayments.com/checkout/payment-api.js"></script>-->
    <script src="payment-api.js"></script>
    <script> 
		window.blur();
      window.DNAPayments.openPaymentWidget({
	    invoiceId: "<?= $invoice_id?>",
	    backLink: "https://bigi.mybigcommerce.com/thankyou/",
	    failureBackLink: "https://app.channelsahead.co.uk/dnapayment/failure.php",
	    postLink: "http://dnapayments.247commerce.co.uk/test/updateOrder.php",
	    failurePostLink: "http://dnapayments.247commerce.co.uk/test/updateFailed.php",
	    language: "EN",
	    description: "Order payment",
	    accountId: "bigcommerce",
	    phone: "7036336658",
		//transactionType:"SALE",
	    terminal: "8911a14f-61a3-4449-a1c1-7a314ee5774c",
	    amount: <?php echo $_GET['amount']; ?>,
	    currency: "GBP",
	    accountCountry: "GB",
	    accountCity: "London",
	    accountStreet1: "14 Tottenham Court Road",
	    accountEmail: "test@test.com",
	    accountFirstName: "Paul",
	    accountLastName: "Smith",
	    accountPostalCode: "W1T 1JY",
	    auth: {
	    	"access_token": "<?php echo $response['access_token']; ?>",
	    	"expires_in": 7200,
	    	"refresh_token": "",
	    	"scope": "payment integration_seamless",
	    	"token_type": "Bearer"
	    }
	});
    </script>
  </body>
</html>