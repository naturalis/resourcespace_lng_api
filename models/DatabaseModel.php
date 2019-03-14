<?php

namespace RsApi;

class DatabaseModel {
	
	protected $_settings;
	protected $_mysqli;
	protected $_stmt;
	protected $_res;
	protected $_config;
	
	public function __construct ($config = false) {
		if (!$config || !is_object($config) || !method_exists($config, 'getDbSettings')) {
			throw new \Exception('Database should be initialised with valid Config object!');
		}
		$this->_settings = $config->getDbSettings();
	}
	
	public function __destruct () {
		$this->_mysqli()->close();
	}
		
	protected function _mysqli () {
		if (!($this->_mysqli instanceof \MySQLi)) {
			$this->_mysqli = new \mysqli(
				$this->_settings->host,
				$this->_settings->user,
				$this->_settings->password,
				$this->_settings->database
			);
			
			if ($this->_mysqli->connect_errno) {
				throw new \Exception("Failed to connect to MySQL: " . 
					$this->_mysqli->connect_error);
			}
			
			// Error reporting
			$driver = new \mysqli_driver();
			$driver->report_mode = MYSQLI_REPORT_STRICT;
		}
		return $this->_mysqli;
	}
	
	protected function _prepare ($query) {
		if (!($this->_stmt = $this->_mysqli()->prepare($query))) {
			throw new \Exception("MySQLi prepare failed: " . $this->_mysqli()->error);
		}
	}
	
	protected function _execute () {
		if (!$this->_stmt->execute()) {
			throw new \Exception("MySQLi execute failed: " . $this->_stmt->error);
		}
	}
	
	protected function _fetch ($params = false) {
		$this->_freeResult();
		$this->_bindParameters($params);
		$this->_execute();
		$this->_stmt->bind_result($this->_res);
		$this->_stmt->fetch();
		$this->_stmt->close();
		return $this->_res;
	}
	
	protected function _insert ($params) {
		$this->_freeResult();
		$this->_bindParameters($params);
		$this->_execute();
		$id = $this->_stmt->insert_id;
		$this->_stmt->close();
		return $id;
	}
	
	protected function _update ($params) {
		$this->_freeResult();
		$this->_bindParameters($params);
		$this->_execute();
		$affected = $this->_stmt->affected_rows;
		$this->_stmt->close();
		return $affected;
	}
	
	protected function _escape ($str) {
		return $this->_mysqli()->real_escape_string($str);
	}
	
	/**
	 * Expects parameters in the form of [types => [value1, value2, etc]]
	 */
	protected function _bindParameters ($params) {
		if (empty($params) || !is_array($params) || count($params) > 1) {
			throw new \Exception('Error! Bind parameters should be passed as ' . 
				'[(str)types => (array)[value1, value2, etc]');
		}
		// Values should be passed by reference
		$bindParams = [];
		foreach (array_values($params)[0] as $key => $value) {
			$bindParams[$key] = &array_values($params)[0][$key];
		}
		// Prepend types string
		array_unshift($bindParams, key($params));
		$bind = new \ReflectionMethod('mysqli_stmt', 'bind_param'); 
      	$bind->invokeArgs($this->_stmt, $bindParams); 
	}
	
	private function _freeResult () {
		if ($this->_stmt && $this->_stmt->num_rows() != 0) {
			$this->_stmt->free_result();
		}
	}
}