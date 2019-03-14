<?php

namespace RsApi;

abstract class AbstractController {
	
	// Config object
	protected $_config;
	// Database handler
	protected $_dbh;
	// Login verification
	protected $_loginSuccessful = false;
	// RS user id of api user (set after successful login)
	protected $_apiUserId;
	// Api response
	protected $_response;
	
	public function __construct ($configPath = false) {
		if (!$configPath) {
			throw new \Exception('Error! '. get_class($this) . ' should be initialised ' .
				'with path to RS config file');
		}
		$this->_config = new ConfigController($configPath);
		$this->_initResponse();
	}
	
	// Poor man's option to check if file is an image
	public function isImage ($path) {
		return getimagesize($path) !== false;
	}
	
	protected function _checkApiCredentials ($apiKey = false, $isAdmin = false) {
		if (!$apiKey) {
			throw new \Exception('Error! No api key provided');
		}
		$userData = $this->_decryptApiKey($apiKey);
		// RS should return array with user and hashed password; 
		// if incorrect the aray only contains a single element
		if (count($userData) == 2) {
			list($userName, $hashedPassword) = $userData;
			$this->_apiUserId = $this->_dbh->userLogin($userName, $hashedPassword, $isAdmin);
			$this->_loginSuccessful = !empty($this->_apiUserId) ? true : false;
			return true;
		}
		return false;
	}
	
	protected function _makeApiKey ($userName, $hashedPassword) {
        return strtr(base64_encode($this->_convert($userName . "|" . $hashedPassword, 
        	$this->_config->getRsApiScrambleKey())), '+/=', '-_,');
	}
	
	protected function _decryptApiKey ($key) {
		$key = $this->_convert(base64_decode(strtr($key, '-_,', '+/=')), 
			$this->_config->getRsApiScrambleKey());
   		return explode("|", $key);
	}
	
	// Exact clone of RS function 
	protected function _convert ($text, $key = '') {
	    // return text unaltered if the key is blank
	    if ($key == '') {
	        return $text;
	    }
	
	    // remove the spaces in the key
	    $key = str_replace(' ', '', $key);
	    if (strlen($key) < 8) {
	        exit('key error');
	    }
	    // set key length to be no more than 32 characters
	    $key_len = strlen($key);
	    if ($key_len > 32) {
	        $key_len = 32;
	    }
	
	    // A wee bit of tidying in case the key was too long
	    $key = substr($key, 0, $key_len);
	
	    // We use this a couple of times or so
	    $text_len = strlen($text);
	
	    // fill key with the bitwise AND of the ith key character and 0x1F, padded to length of text.
	    $lomask = str_repeat("\x1f", $text_len); // Probably better than str_pad
	    $himask = str_repeat("\xe0", $text_len);
	    $k = str_pad("", $text_len, $key); // this one _does_ need to be str_pad
	
	    // {en|de}cryption algorithm
	    $text = (($text ^ $k) & $lomask) | ($text & $himask);
	
	    return $text;
	} 
	
	// By default response is an object with just an (empty) error property
	protected function _initResponse ($error = null) {
		$this->_response = new \stdClass();
		$this->_response->error = $error;
		return $this->_response;
	}
	
	protected function _setResponseError ($error) {
		$this->_response->error = $error;
		return $this->_response;
	}
	
	// Copy of RS method but with redundant options stripped off
	protected function _runCommand ($command) {
	    # Works like system(), but returns the complete output string rather than just the
	    # last line of it.
	    $process = @proc_open($command, [1 => ['pipe', 'w']], $pipe, NULL, NULL, ['bypass_shell' => true]);
	    if (is_resource($process)) {
	        return trim(stream_get_contents($pipe[1]));
	    }
	    return '';
	}
	
}