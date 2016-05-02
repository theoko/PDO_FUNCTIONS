<?php

try {
	
	$servername = 'localhost';
	$dbname = 'pdo';
	$username = 'pdo';
	$password = 'pass';
	
	$dbc = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	
} catch(PDOException $e) {
	
	echo $e->getMessage();
}

?>
