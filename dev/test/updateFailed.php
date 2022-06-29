<?php	
	$data = file_get_contents('php://input');
	try{
		$fp = fopen("failed.txt", "w");
		fwrite($fp, $data);
		fclose($fp);
	}catch(Exception $e) {
	}
?>