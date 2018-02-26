<?php

namespace RsApi;

class ConfigController {

	private $_dbSettings;
	private $_rsBaseUrl;
	private $_rsApiScrambleKey;
	private $_rsConfigPath;
	
	public function __construct ($config = false) {
		if ($config) {
			$this->_loadRsConfig($config);
		}
	}
	
	public function getDbSettings () {
		return $this->_dbSettings;
	}
	
	public function getRsBaseUrl () {
		return $this->_rsBaseUrl;
	}
	
	public function getRsApiScrambleKey () {
		return $this->_rsApiScrambleKey;
	}
	
	private function _loadRsConfig ($config = false) {
		if (!is_readable($config)) {
			throw new \Exception ('Error! Check path ' . $config . ' to RS config file');
		}
		require_once $config;
		// Database
		$db = new \stdClass();
		$db->host = $mysql_server ?? false;
		$db->user = $mysql_username ?? false;
		$db->password = $mysql_password ?? false;
		$db->database = $mysql_db ?? false;
		$this->_dbSettings = $db;		
		// Base url
		$this->_rsBaseUrl = $baseurl ?? false;
		// Scramble key
		$this->_rsApiScrambleKey = $api_scramble_key ?? false;
	}
	
}