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
		}else{
			$sellerdb = '247c'.strtotime(date('y-m-d h:m:s'));
			$sql = 'insert into dna_token_validation(email_id,sellerdb,acess_token,store_hash) values("'.$email.'","'.$sellerdb.'","'.$access_token.'","'.$store_hash.'")';
			$conn->exec($sql);
		}
	}
}
?>