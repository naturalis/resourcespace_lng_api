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
		if (!($this->_mysqli instanceof MySQLi)) {
			$this->_mysqli = new \mysqli(
				$this->_settings->host,
				$this->_settings->user,
				$this->_settings->password,
				$this->_settings->database
			);
			if ($this->_mysqli->connect_errno) {
				throw new \Exception("Failed to connect to MySQL: (" . 
					$this->_mysqli->connect_errno . ") " . $this->_mysqli->connect_error);
			}
		}
		return $this->_mysqli;
	}
	
	protected function _prepare ($query) {
		if (!($this->_stmt = $this->_mysqli()->prepare($query))) {
			throw new \Exception("Prepare failed: (" . $this->_mysqli()->errno . ") " . 
				$this->_mysqli()->error);
		}
	}
	
	protected function _fetch ($params = false) {
		$this->_bindParameters($params);
		$this->_stmt->execute();
		$this->_stmt->bind_result($this->_res);
		$this->_stmt->fetch();
		$this->_stmt->close();
		return $this->_res;
	}
	
	protected function _insert ($params) {
		$this->_bindParameters($params);
		$this->_stmt->execute();
		$id = $this->_stmt->insert_id;
		$this->_stmt->close();
		return $id;
	}
	
	protected function _update ($params) {
		$this->_bindParameters($params);
		$this->_stmt->execute();
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
		if (empty($params) || !is_array($params)) {
			throw new \Exception('Bind parameters should be passed as array: [types => [value1, value2, etc]');
		}
		// Types is the $params array key
		$bind[] = key($params);
		// Values are passed as the $params value; the values themselves should be passed by reference
		foreach (array_values($params)[0] as $value) {
			$bind[] = &$value;
		}
		call_user_func_array([$this->_stmt, 'bind_param'], $bind);
	}
	
}