<?php

namespace RsApi;

class ConfigController {

	public $db;
	public $rsBaseUrl;
	public $rsApiScrambleKey;
	public $rsConfigPath;
	
	public function __construct ($config = false) {
		if ($config) {
			$this->_loadRsConfig($config);
		}
	}
	
	public function setRsConfig ($config) {
		$this->_rsConfigPath = $config;
		if (!is_readable($this->_rsConfigPath)) {
			throw new \Exception ('Error! Check config path ' . $this->_rsConfigPath);
		}
	}

	private function _loadRsConfig ($config = false) {
		$this->setRsConfig($config);
		require_once $this->_rsConfigPath;
		
		// Database
		$db = new \stdClass();
		$db->host = $mysql_server ?? false;
		$db->user = $mysql_username ?? false;
		$db->password = $mysql_password ?? false;
		$db->database = $mysql_db ?? false;
		$this->db = $db;
		
		// Base url
		$this->rsBaseUrl = $baseurl ?? false;
		
		// Scramble key
		$this->rsApiScrambleKey = $api_scramble_key ?? false;
	}
	
		
		
		
	
	
}