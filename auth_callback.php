<?php
   require_once('db-config.php');
   require_once('config.php');
   ////$data = json_decode(file_get_contents('php://input'), true);
   $data = $_REQUEST;
   $fp = fopen("auth.txt", "w");
   fwrite($fp, serialize($data));
   fclose($fp);

   $postData = array(
                    'client_id' => APP_CLIENT_ID,
                    'client_secret'  => APP_CLIENT_SECRET,
                    'redirect_uri' => BASE_URL.'auth_callback.php',
                    'grant_type' => 'authorization_code',
                    'code' => $_GET['code'],
                    'scope' => $_GET['scope'],
                    'context' => $_GET['context']
                    );

    $post_fields = http_build_query($postData);
    ////exit;
    ////echo '<br />';

    $url = 'https://login.bigcommerce.com/oauth2/token';   

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    ////curl_setopt($ch, CURLOPT_HEADER, 1);  // include headers in result
    ////curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //do not chk for peer ssl
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    ////curl_setopt($ch, CURLOPT_SSLVERSION,6);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheaders); // send my headers    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);   
    
    if(!($data = curl_exec($ch))) {  
        echo "request-token > Request Curl Error: ".curl_error($ch);                       
    }       
    else { 
		$fp = fopen("authres.txt", "w");
	   fwrite($fp, serialize($data));
	   fclose($fp);
        ////echo $data; 
        $response = json_decode($data, true); 
        
        /*print '<pre />';
        print_r($response);*/

        if(isset($response['access_token'])) {
			storeTokenData($response);
            /////echo "App installed successfully.";
        }

		$data = $_REQUEST;
		$fp = fopen("token.txt", "w");
		fwrite($fp, serialize($response));
		fclose($fp);

		if(isset($response['user']['email'])){
			$email = $response['user']['email'];
			header("Location: index.php?bc_email_id=".$email);
		}
    }  

    curl_close($ch);
    exit;
function storeTokenData($response){
	$email = '';
	$access_token = '';
	$store_hash = '';
	if(isset($response['user']['email'])){
		$email = $response['user']['email'];
	}
	if(isset($response['access_token'])){
		$access_token = $response['access_token'];
	}
	if(isset($response['context'])){
		$store_hash = str_replace("stores/","",$response['context']);
	}
	if(!empty($email) && !empty($access_token) && !empty($store_hash)){
		$conn = getConnection();
		$stmt = $conn->prepare("select * from dna_token_validation where email_id='".$email."'");
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		
		if (count($result) > 0) {
			$sql = 'update dna_token_validation set acess_token="'.$access_token.'",store_hash="'.$store_hash.'" where email_id="'.$email.'"';
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			createCustomPage($email,$store_hash,$access_token);
			createWebhooks($email,$store_hash,$access_token);
		}else{
			$sellerdb = '247c'.strtotime(date('y-m-d h:m:s'));
			$sql = 'insert into dna_token_validation(email_id,sellerdb,acess_token,store_hash) values("'.$email.'","'.$sellerdb.'","'.$access_token.'","'.$store_hash.'")';
			$conn->exec($sql);
			createCustomPage($email,$store_hash,$access_token);
			createWebhooks($email,$store_hash,$access_token);
		}
	}
}
function createCustomPage($email_id,$store_hash,$acess_token){
	
	$conn = getConnection();
	
	$url = STORE_URL.$store_hash.'/v2/pages';
		$header = array(
			"X-Auth-Token: ".$acess_token,
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
				  "body"=> "<head>
							<link rel=\"stylesheet\" href=\"".BASE_URL."/css/order-confirmation.css\">
							<script src=\"".BASE_URL."js/jquery.min.js\"></script>
							<script src=\"".BASE_URL."js/order-confirmation.js\"></script>
							</head>
							<body>
							<h1>Please Wait</h1>
							</body>",
				  "channel_id"=> 1,
				  "has_mobile_version"=> false,
				  "is_customers_only"=> false,
				  "is_homepage"=> false,
				  "is_visible"=> false,
				  "mobile_body"=> "",
				  "name"=> "Custom Order Confirmation",
				  "parent_id"=> 0,
				  "search_keywords"=> "",
				  "sort_order"=> 0,
				  "type"=> "raw",
				  "url"=> "/custom-order-confirmation"
				);
		$request = json_encode($request,true);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		//print_r($res);exit;
		$log_sql = 'insert into api_log(email_id,type,action,api_url,api_request,api_response) values("'.$email_id.'","BigCommerce","Custom Page","'.addslashes($url).'","'.addslashes($request).'","'.addslashes($res).'")';
		//echo $log_sql;exit;
		$conn->exec($log_sql);
		if(!empty($res)){
			$check_errors = json_decode($res);
			if(isset($check_errors->errors)){
			}else{
				if(json_last_error() === 0){
					$res = json_decode($res,true);
					if(isset($res['id'])){
						$sqli = "insert into 247custompages(email_id,page_bc_id,api_response) values('".$email_id."','".$res['id']."','".addslashes(json_encode($res))."')";
						$conn->exec($sqli);
					}
				}
			}
		}
}
function createWebhooks($email_id,$store_hash,$acess_token){
	$conn = getConnection();
	$webhooks = array(
					array(
						"id"=>1,
						"scope"=>"store/order/statusUpdated",
						"destination"=>BASE_URL."webhooks/updateOrderStatus.php?bc_email_id=".$email_id
					)
				);
	foreach($webhooks as $k=>$v){
		$url = STORE_URL.$store_hash.'/v3/hooks';
		$header = array(
			"X-Auth-Token: ".$acess_token,
			"Accept: application/json",
			"Content-Type: application/json"
		);
		$request = array(
						"scope"=>$v['scope'],
						"destination"=>$v['destination'],
						"is_active"=>true
					);
		$request = json_encode($request);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$res = curl_exec($ch);
		curl_close($ch);
		//print_r($res);exit;
		if(!empty($res)){
			$check_errors = json_decode($res);
			if(isset($check_errors->errors)){
			}else{
				if(json_last_error() === 0){
					$res = json_decode($res,true);
					if(isset($res['data']['id'])){
						$data = $res['data'];
						$sqli = "insert into 247webhooks(email_id,webhook_bc_id,scope,destination,api_response) values('".$email_id."','".$data['id']."','".$data['scope']."','".$data['destination']."','".stripslashes(json_encode($res))."')";
						$conn->query($sqli);
					}
				}
			}
		}
	}
}
?>