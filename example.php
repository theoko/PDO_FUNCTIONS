<?php

require_once 'class.database.php';

$db = new Database('127.0.0.1', 'database', 'user', 'password');

$db->debug();

$db->customPreparedQuery("insert into links(title, url) values('Test', 'example.com')");

$status = $db->insertOrFail('links', [
	'title' => 'Test',
	'url' => [
		'type' => 'string', // by default it is 'string', you can use 'integer' as well
		'value' => 'example.com',
	],
]);

if($status) {
	echo "success";
} else {
	echo "exists!";
}
