<?php
/**
	* Db connection Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
	function getConnection(){
		$username = "m2devoutsidevpc";
		$password = "247Commerce*123";
		$database = "dna_pay";
		$host = "m2devoutsidevpc.co13c6zl8ys8.eu-west-1.rds.amazonaws.com";
		//$conn = mysqli_connect($host,$username,$password,$database);
		
		$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		return $conn;
	}
		
		
?>