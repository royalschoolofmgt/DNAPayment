<?php	
	$data = file_get_contents('php://input');
	try{
		$fp = fopen("thankyou.txt", "w");
		fwrite($fp, $data);
		fclose($fp);
	}catch(Exception $e) {
	}
?>