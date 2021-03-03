<?php
$url = 'https://bigi.mybigcommerce.com/api/storefront/checkouts/6ea3e632-bd44-4afb-9ad4-4b98266af565';
		//print_r($url);exit;
		$header = array(
				"Accept: application/json",
				"Content-Type: application/json"
			);
		$ch = curl_init($url); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		//curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		//curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		
		print_r($res);exit;
?>