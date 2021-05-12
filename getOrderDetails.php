<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once('config.php');
require_once('db-config.php');

header('access-control-allow-origin: *');

$final_data = array();
$final_data['status'] = false;
$final_data['data'] = array();
$final_data['msg'] = '';
	
if(isset($_REQUEST['authKey'])){
	$conn = getConnection();
	$invoiceId = json_decode(base64_decode($_REQUEST['authKey']),true);
	if($invoiceId != ""){
		$stmt_order_payment = $conn->prepare("select * from order_details where invoice_id='".$invoiceId."'");
		$stmt_order_payment->execute();
		$stmt_order_payment->setFetchMode(PDO::FETCH_ASSOC);
		$result_order_payment = $stmt_order_payment->fetchAll();
		if (isset($result_order_payment[0])) {
			$result_order_payment = $result_order_payment[0];
			$stmt = $conn->prepare("select * from dna_token_validation where email_id=? and validation_id=?");
			$stmt->execute([$result_order_payment['email_id'],$result_order_payment['token_validation_id']]);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$result = $stmt->fetchAll();
			//print_r($result[0]);exit;
			if (isset($result[0])) {
				$result = $result[0];
				$acess_token = $result['acess_token'];
				$store_hash = $result['store_hash'];
				$order_id = $result_order_payment['order_id'];
				$url_T = STORE_URL.$store_hash.'/v2/orders/'.$order_id; 

				$header = array(
					"store_hash: ".$store_hash,
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
				$result_T = '';

				$ch_T = curl_init($url_T);
				curl_setopt($ch_T, CURLOPT_HTTPHEADER, $header); // send my headers
				curl_setopt($ch_T, CURLOPT_RETURNTRANSFER, true); // return result in a variable
				curl_setopt($ch_T, CURLOPT_SSL_VERIFYPEER, false);
				//curl_setopt($ch_T, CURLOPT_SSLVERSION, 3);     
				//curl_setopt($ch_T, CURLOPT_POST, true); 
				//curl_setopt($ch_T, CURLOPT_POSTFIELDS, $post_details);
				curl_setopt($ch_T, CURLOPT_FOLLOWLOCATION, true);               

				$result_T = curl_exec($ch_T);
				//print_r($result_T);exit;
				$check_errors = json_decode($result_T);
				if(isset($check_errors->errors)){
				}else{
					if(json_last_error() === 0){
						$response = json_decode($result_T,true);
						if(isset($response['id'])){
							
			
							$final_data['status'] = true;
							$final_data['data'] = $response;
							
							$url_store = STORE_URL.$store_hash.'/v2/store';
							
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url_store);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							$res_store = curl_exec($ch);
							curl_close($ch);
							if(!empty($res_store)){
								$res_store = json_decode($res_store,true);
								if(isset($res_store['secure_url'])){
									$final_data['data']['storeData'] = $res_store;
								}
							}
							$images = array();
							
							/* Api for get products using order_id */
							$url_P = STORE_URL.$store_hash.'/v2/orders/'.$order_id.'/products'; 
							$result_P = '';
							$ch_P = curl_init($url_P);
							curl_setopt($ch_P, CURLOPT_HTTPHEADER, $header); // send my headers
							curl_setopt($ch_P, CURLOPT_RETURNTRANSFER, true); // return result in a variable
							curl_setopt($ch_P, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch_P, CURLOPT_FOLLOWLOCATION, true);			

							$result_P = curl_exec($ch_P);
							//print_r($result_P);exit;
							$check_errors = json_decode($result_P);
							if(isset($check_errors->errors)){
							}else{
								if(json_last_error() === 0){
									$response_P = json_decode($result_P,true);
									foreach($response_P as $k=>$v){
										if(isset($v['product_id'])){
											$url_I = STORE_URL.$store_hash.'/v3/catalog/products/'.$v['product_id'].'/images';
											$result_I = '';

											$ch_I = curl_init($url_I);
											curl_setopt($ch_I, CURLOPT_HTTPHEADER, $header); // send my headers
											curl_setopt($ch_I, CURLOPT_RETURNTRANSFER, true); // return result in a variable
											curl_setopt($ch_I, CURLOPT_SSL_VERIFYPEER, false);
											curl_setopt($ch_I, CURLOPT_FOLLOWLOCATION, true); 				

											$result_I = curl_exec($ch_I);
											//print_r($result_P);exit;
											$check_errors = json_decode($result_I);
											if(isset($check_errors->errors)){
											}else{
												if(json_last_error() === 0){
													$response_I = json_decode($result_I,true);
													if(isset($response_I['data'])){
														foreach($response_I['data'] as $k1=>$v1){
															if($v['product_id'] == $v1['product_id']){
																$b64image = base64_encode(file_get_contents($v1['url_thumbnail']));
																$type = pathinfo($v1['url_thumbnail'], PATHINFO_EXTENSION);
																//echo $type;exit;
																$response_I['data'][$k1]['encodedImage'] = 'data:image/' . $type . ';base64,' . $b64image;
																//print_r($response_I['data'][$k]);exit;
															}
														}
														$response_P[$k]['productImages'] = $response_I['data'];
													}
												}
											}
										}
									}
									$final_data['data']['productsData'] = $response_P;
								}
							}
							/* Api for get products using order_id end */
						}else{
							$final_data['msg'] = 'No data found';
						}
					}
				}
			}
		}
	}
}
echo json_encode($final_data,true);exit;

