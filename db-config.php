<?php
/**
	* Db connection Page
	* Author 247Commerce
	* Date 22 FEB 2021
*/
	function getConnection(){
		$username = "root";
		$password = "";
		$database = "dna_pay";
		$host = "localhost";
		//$conn = mysqli_connect($host,$username,$password,$database);
		
		$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		return $conn;
	}
		
		
?>