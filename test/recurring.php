<?php
	header("Access-Control-Allow-Origin: *");
	$post_fields = array(
		"scope" => "payment integration_seamless webapi",
        "client_id"  => "bigcommerce",
        "client_secret" => "BRgNFy=vup=*3Zx7M_Q8HP@e!SEc5T_j8?N6A3Ta!e38S!D4uRG2y@htrm5h2tYR",
        "grant_type" => "client_credentials",
        "invoiceId" => "Test-REC-8",
        "amount" => 0.00,
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
			amount: 0,
			currency: 'GBP',
			transactionType: 'RECURRING',
			recurringTransactionType: 'AUTH',
			periodicType: 'ucof',
			invoiceId: 'Test-REC-8',
			terminal: '8911a14f-61a3-4449-a1c1-7a314ee5774c',
			backLink: 'https://dnapayments.247commerce.co.uk/test/success.html',
			failureBackLink: 'https://dnapayments.247commerce.co.uk/test/failure.html',			
			postLink: 'https://dnapayments.247commerce.co.uk/test/updateOrder.php',
			failurePostLink: 'https://dnapayments.247commerce.co.uk/test/updateFailed.php',
			description: 'Order payment',
			accountId: 'uuid0000015',
			accountCountry: 'GB',
			accountCity: 'London',
			accountStreet1: '124 Fulham Rd',
			accountEmail: 'demo@dnapayments.com',
			accountFirstName: 'John',
			accountLastName: 'Doe',
			accountPostalCode: 'SW1 2HS',
			auth: {
				"access_token": "<?php echo $response['access_token']; ?>",
				"expires_in": 7200,
				"refresh_token": "",
				"scope": "payment integration_seamless webapi",
				"token_type": "Bearer"
			}
		});

			

    </script>
  </body>
</html>