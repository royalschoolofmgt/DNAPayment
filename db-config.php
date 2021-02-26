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
		$con = mysqli_connect($host,$username,$password,$database);
		return $con;
	}
		
		
?>