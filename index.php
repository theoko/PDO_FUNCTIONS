<?php

try {
	
	$servername = 'localhost';
	$dbname = 'pdo';
	$username = 'pdo';
	$password = 'pass';
	
	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	
} catch(PDOException $e) {
	
	echo $e->getMessage();
}

if (isset($_POST['add'])) {
	
	// STATEMENT
	$insert = $conn->prepare("INSERT INTO table (title, title_id, content) VALUES (:title, :title_id, :content)");
	
	// BIND PARAMETERS
	$insert->bindParam(':title', $_POST['title']);
	$insert->bindParam(':title_id', $_POST['title_id']);
	$insert->bindParam(':content', $_POST['content']);
	
	$insert->execute();
}

if (isset($_POST['save'])) {
	$save = $conn->prepare("UPDATE table SET title = ?, title_id = ?, content = ? WHERE id = ?");

	$save->bindParam(1, $_POST['title']);
	$save->bindParam(2, $_POST['title_id']);
	$save->bindParam(3, $_POST['content']);
	$save->bindParam(4, $_POST['id']);

	$save->execute();
}

?>
