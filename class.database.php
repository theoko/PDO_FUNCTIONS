<?php

class Database {
	protected $server;
	protected $db;
	protected $user;
	protected $password;

	public function __construct($server, $db, $user, $password) {
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
		}
	}

	public function convertType($type) {
		if($type == 'default') {
			return PDO::PARAM_STR;
		} else if ($type == 'integer') {
			return PDO::PARAM_INT;
		} else if ($type == 'string') {
			return PDO::PARAM_STR;
		}
	}

	public function checkIfExists($table, $type, $value) {
		/*
		* @return true|false
		*/

		$stmt = $this->conn->prepare("select ".$type." from ".$table." where ".$type." = :".$type);
		$stmt->bindValue(':'.$type, PDO::PARAM_STR);
		$stmt->execute();

		$returnedRows = $stmt->rowCount();

		if($returnedRows > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function insertOrFail($table, array $data) {
		/*
		*	This method will ensure the table is unique. It will only insert a record if it does not already exist.
		*/

		foreach($data as $key => $d) {
			$exists = false;

			if(is_array($d)) {
				$exists = $this->checkIfExists($table, $key, $d['value']);
			} else {
				$exists = $this->checkIfExists($table, $key, $d);
			}

			if($exists == true) {
				return false;
			}
		}

		$this->insert($table, $data);
	}

	public function insert($table, array $data) {

		$query = "insert into " . $table . "(";
		$i=1;
		foreach($data as $key => $d) {
			$query .= $key;
			if($i < count($data)) {
				$query .= ", ";
			}
			$i++;
		}

		$query .= ") values(";

		$i=1;
		foreach($data as $key => $d) {
			$query .= ":".$key;
			if($i < count($data)) {
				$query .= ", ";
			}
			$i++;
		}

		$query .= ")";

		try {
			$stmt = $this->conn->prepare($query);
			$i=1;
			foreach($data as $key => $d) {
				if(is_array($d)) {
					$stmt->bindValue(':'.$key, $d['value'], $this->convertType($d['type']));
				} else {
					$stmt->bindValue(':'.$key, $d, PDO::PARAM_STR);
				}
				$i++;
			}

			$stmt->execute();

			return true;
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}

	}

	public function update($table, array $options) {
		$query = "update ".$table." set ".$options['field']." = :".$options['field']." where id = ".$options['id'];

		$stmt = $this->conn->prepare($query);
		if(isset($options['type'])) {
			$stmt->bindValue(':'.$options['field'], $options['value'], $this->convertType($options['type']));
		} else {
			$stmt->bindValue(':'.$options['field'], $options['value'], $this->convertType('string'));
		}
		$stmt->execute();
	}

	public function multiUpdate($table, array $identifiers, array $data) {
		foreach($identifiers as $id) {
			$this->update($table, [
				'id' => $id,
				$data,
			]);
		}
	}

	public function customPreparedQuery($query) {
		$this->conn->prepare($query)->execute();
	}

	public function debug() {
		echo "SERVER: ".$this->server.PHP_EOL;
		echo "DATABASE: ".$this->db.PHP_EOL;
		echo "USER: ".$this->user.PHP_EOL;
		echo "PASSWORD: ".$this->password.PHP_EOL;
		if(is_object($this->conn)) {
			echo "PDO is Object, connection succeeded!".PHP_EOL;
		}
	}
}
