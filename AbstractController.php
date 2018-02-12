<?php

class AbstractController {
	
	protected $dbSettings = [];
	
	
	public function __contructor () {
		
	}

	public function __destructor () {
		
	}
	
	public function setRsConfig ($config) {
		if (!is_readable($config)) {
			throw new Exception ('Cannot read config file ' . $config);
		}
		require_once $config;
		
	}
	
	
}