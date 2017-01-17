<?php

class Database {
	protected $server;
	protected $db;
	protected $user;
	protected $password;
	protected $debug;

	public function __construct($server, $db = "", $user, $password, $debug = false) {
		$this->server = $server;
		$this->db = $db;
		$this->user = $user;
		$this->password = $password;
		$this->debug = $debug;
		$this->conn = $this->connection();
	}

	public function __destruct() {
		$this->conn = NULL;
	}

	public function connection() {
		try {
			if(stristr($this->server, ':')) {
				$server = explode(':', $this->server)[0];
				$port = explode(':', $this->server)[1];
				if(empty($this->db)) {
					$conn = new PDO("mysql:host=".$server.";port=".$port, $this->user, $this->password);
				} else {
					$conn = new PDO("mysql:host=".$server.";dbname=".$this->db.";port=".$port, $this->user, $this->password);
				}
			} else {
				$conn = new PDO("mysql:host=".$this->server.";dbname=".$this->db, $this->user, $this->password);
			}

			return $conn;
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return NULL;
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

	public function checkOperator($operator) {
		$operator = strtolower($operator);

		if($operator != 'or' && $operator != 'and') {
			$operator = 'and';
		}

		return $operator;
	}

	public function checkOrder($order) {
		$order = strtolower($order);

		if($order != 'desc' && $order != 'asc') {
			$order = 'asc';
		}

		return $order;
	}

	public function error(array $error) {
		throw new Exception($error['message'], 1);
	}

	public function get($table, $options = []) {
		$filter = false;
		$query = "select ";

		if(isset($options['type'])) {
			if(is_array($options['type'])) {
				$i = 1;
				foreach($options['type'] as $t) {
					$query .= $t;
					if($i < count($options['type'])) {
						$query .= ", ";
					}
					$i++;
				}
			} else {
				$query .= $options['type'];
			}
		} else {
			$query .= "*";
		}
		$query .= " from ".$table;
		if(isset($options['filter'])) {
			if(is_array($options['filter'])) {
					$i = 1;
					$filter = true;
					$query .= " where";
					foreach($options['filter'] as $k => $f) {
						if(isset($f['search']) && $f['search'] == true) {
							$query .= " ".$k." LIKE :".$k;
						} else {
							$query .= " ".$k." = :".$k;
						}
						if($i < count($options['filter'])) {
							if(is_array($f) && isset($f['operator'])) {
								$query .= " ".$this->checkOperator($f['operator']);
							} else {
								$query .= " and";
							}
						}
						$i++;
					}
			}
		}
		if(isset($options['sort'])) {
			if(is_array($options['sort'])) {
				if(isset($options['sort']['by'])) {
					$query .= " order by ".$options['sort']['by'];
					if(isset($options['sort']['order'])) {
						$query .= " ".$this->checkOrder($options['sort']['order']);
					}
				}
			}
		}
		if(isset($options['count'])) {
			$query .= " limit ".$options['count'];
		}

			try {
				$stmt = $this->conn->prepare($query);
				if($filter) {
					foreach($options['filter'] as $k => $f) {
						if(isset($f['type'])) {

							if(isset($f['search']) && $f['search'] == true) {
								$stmt->bindValue(':'.$k, '%'.$f['value'].'%', $this->convertType($f['type']));
							} else {
								$stmt->bindValue(':'.$k, $f['value'], $this->convertType($f['type']));
							}

						} else {

							if(isset($f['search']) && $f['search'] == true) {
								$stmt->bindValue(':'.$k, '%'.$f.'%', $this->convertType('default'));
						  } else {
								$stmt->bindValue(':'.$k, $f, $this->convertType('default'));
							}

						}
					}
				}
				$stmt->execute();

				$rows = $stmt->rowCount();

				$data = [];
				if($rows > 0) {
					$stmt->setFetchMode(PDO::FETCH_ASSOC);

					$iterator = new IteratorIterator($stmt);
					foreach($iterator as $k => $d) {
						$data[][$k] = $d;
					}
				}

				return $data;
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return [];
		}
	}

	public function checkIfExists($table, $type, $value) {
		/*
		* @return true|false
		*/
		try {
			$stmt = $this->conn->prepare("select ".$type." from ".$table." where ".$type." = :".$type);
			$stmt->bindValue(':'.$type, $value, $this->convertType('default'));
			$stmt->execute();

			$returnedRows = $stmt->rowCount();

			if($returnedRows > 0) {
				return true;
			} else {
				return false;
			}
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
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
					$stmt->bindValue(':'.$key, $d, $this->convertType('default'));
				}
				$i++;
			}

			$stmt->execute();

			return true;
		} catch (Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return false;
		}

	}

	public function update($table, array $options) {
		$query = "update ";
		$query .= $table;
		$query .= " set ";
		$query .= $options['field'];
		$query .= " = :".$options['field'];
		$query .= " where ";
		if(isset($options['column'])) {

			if(is_array($options['column'])) {
				foreach($options['column'] as $key => $col) {
					$id = $key;
					$id_value = $col;
				}
				if(is_numeric($id_value)) {
					$query .= $id." = ".$id_value;
				} else {
					$query .= $id." = '".$id_value."'";
				}

			} else {
				throw new Exception("options:column should be an array", 1);
			}

		} else {
			if(!isset($options['id'])) {
				$this->error(['message' => 'if options:column (array) is not set options:id (int) should be set']);
			}
			$query .= "id = ".$options['id'];
		}

		try {
			$stmt = $this->conn->prepare($query);
			if(isset($options['type'])) {
				$stmt->bindValue(':'.$options['field'], $options['value'], $this->convertType($options['type']));
			} else {
				$stmt->bindValue(':'.$options['field'], $options['value'], $this->convertType('string'));
			}
			$stmt->execute();

			return true;
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return false;
		}
	}

	public function multiUpdate($table, array $identifiers, array $data) {
		try {
			foreach($identifiers as $key => $field) {
				$this->update($table, [
					$key => $field,
					'options' => $data,
				]);
			}
			return true;
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return false;
		}
	}

	public function multiUpdateUsingId($table, array $identifiers, array $data) {
		try {
			foreach($identifiers as $id) {
				$this->update($table, [
					'id' => $id,
					'options' => $data,
				]);
			}
			return true;
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return false;
		}
	}

	public function customQuery($query) {
		try {
			$stmt = $this->conn->prepare($query);
		} catch(Exception $e) {
			if($this->debug) {
				echo $e->getMessage();
			}
			return false;
		}
		if($stmt->execute()) {
			return true;
		} else {
			return false;
		}
	}

	public function debug() {
		$data = [];
		$data['server'] = $this->server;
		$data['database'] = $this->db;
		$data['user'] = $this->user;
		$data['password'] = $this->password;
		$data['debug'] = $this->debug;
		if(is_object($this->conn)) {
			$data['connection'] = true;
		} else {
			$data['connection'] = false;
		}

		return $data;

	}
}
