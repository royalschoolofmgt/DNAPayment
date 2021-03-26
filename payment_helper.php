<?php
require_once('config.php');

function authentication($email_id){
	$options = array(
		'method' => 'POST',
		'url' => AUTHENTICATE_URL,
		'formData' =>
		array(
			'scope' => 'payment integration_seamless',
			'client_id' => 'ExampleShop',
			'client_secret' => 'mFE454mEF6kmGb4CDDeN6DaCnmQPf4KLaF59GdqwP',
			'grant_type' => 'client_credentials',
			'invoiceId' => '1234567',
			'amount' => '1',
			'currency' => 'GBP',
			'terminal' => 'b95c9d1f-132f-4e04-92d2-32335c7486ea'
		);
	);
}
?>