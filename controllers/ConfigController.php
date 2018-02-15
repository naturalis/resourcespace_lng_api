<?php

namespace RsApi;

class ConfigController {

	private $_dbSettings;
	private $_rsBaseUrl;
	private $_rsApiScrambleKey;
	private $_rsConfigPath;
	private $_rsApiKeys;
	
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
	
	public function getRsApiKeys () {
		return $this->_rsApiKeys;
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
		// Secret api keys
		$this->_setApiKeys($api_keys_path ?? false);
	}
	
	private function _setApiKeys ($apiKeyPath = false) {
		if (!$apiKeyPath) {
			throw new \Exception ('Error! $api_keys_path variable with valid api keys ' .
				'must be present in RS config file!');
		}
		if (!is_readable($apiKeyPath)) {
			throw new \Exception ('Error! Check path ' . $apiKeyPath . ' to valid api keys');
		}
		$keys = json_decode(file_get_contents($apiKeyPath));
		if (!$keys) {
			throw new \Exception ('Error! ' . $apiKeyPath . ' is not a valid json file');
		}
		$this->_rsApiKeys = $keys;
	}
}