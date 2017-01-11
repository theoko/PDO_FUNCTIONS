<?php

class Database {
	protected $server;
	protected $db;
	protected $user;
	protected $password;

	public function __contruct($server, $db, $user, $password) {
		$this->server = $server;
		$this->db = $db;
		$this->user = $user;
		$this->password = $password;
		$this->conn = $this->connection();
	}

	public function __destruct() {
		$this->conn = NULL;
	}

	public function connection() {
		try {
			$conn = new PDO("mysql:host=".$this->server.";dbname=".$this->db, $this->user, $this->password);

			return $conn;
		} catch(Exception $e) {
			echo $e->getMessage();
			$this->__destruct();
		}
	}

	public function insertOrFail() {

	}

	public function customQuery($query) {

	}
}

$db = new Database('127.0.0.1', 'db', 'pdo', 'pass');
