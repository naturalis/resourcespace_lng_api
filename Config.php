<?php

require_once AbstractController;

class Config extends AbstractController {

	private $_rsConfigPath;
	private $_rsDatabase;
	private $_rsBaseUrl;
	private $_rsApiScrambleKey;
	
	public function __contructor ($config = false) {
		parent::__construct();
		if ($config) {
			$this->loadRsConfig($config);
		}
	}
	
	public function setRsConfigPath ($config) {
		$this->_rsConfigPath = $config;
	}

	public function loadRsConfig ($config = false) {
		if ($config) {
			$this->_rsConfigPath = $config;
		}
		if (!is_readable($this->_rsConfigPath)) {
			throw new Exception ('Error! Check config path ' . $this->_rsConfigPath);
		}
		// Load config
		require_once $this->_rsConfigPath;
		// Database
		$this->_rsDatabase->host = $mysql_server ?? false;
		$this->_rsDatabase->user = $mysql_username ?? false;
		$this->_rsDatabase->password = $mysql_password ?? false;
		$this->_rsDatabase->database = $mysql_db ?? false;
		// Base url
		$this->rsBaseUrl = $baseurl ?? false;
		// Scramble key
		$this->_rsApiScrambleKey = $api_scramble_key ?? false;
	}
	
		
		
		
	
	
}