<?php

/*
 * Processes RS config file
 */

namespace RsApi;

class ConfigController {

	private $_dbSettings;
	private $_rsBaseUrl;
	private $_rsApiScrambleKey;
	private $_rsConfigPath;
	private $_imageMagickPath;
	private $_scrambleKey;
	private $_storageDir;
	private $_noPreviewDir = 'gfx/no_preview/extension/';
	
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
	
	public function getStorageDir () {
		return $this->_storageDir;
	}
	
	public function getNoPreviewDir () {
		return $this->_noPreviewDir;
	}
	
	public function getScrambleKey () {
		return $this->_scrambleKey;
	}
	
	public function getImageMagickPath () {
		return $this->_imageMagickPath;
	}
	
	private function _loadRsConfig ($config = false) {
		if (!is_readable($config)) {
			throw new \Exception ('Error! Check path ' . $config . ' to RS config file');
		}
		$this->_rsConfigPath = $config;
		require_once $this->_rsConfigPath;
		// Database
		$db = new \stdClass();
		$db->host = $mysql_server ?? false;
		$db->user = $mysql_username ?? false;
		$db->password = $mysql_password ?? false;
		$db->database = $mysql_db ?? false;
		$this->_dbSettings = $db;		
		// Base url
		$this->_rsBaseUrl = $baseurl ?? false;
		// Scramble key for api access
		$this->_rsApiScrambleKey = $api_scramble_key ?? false;
		// Scramble key for file paths
		$this->_scrambleKey = $scramble_key ?? false;
		// Scramble key for file paths
		$this->_storageDir = $storagedir ?? dirname(dirname($this->_rsConfigPath)) . "/filestore";
		// Path to ImageMagick
		$this->_imageMagickPath = $imagemagick_path ?? false;
	}
	
	
}