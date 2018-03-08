<?php

namespace RsApi;

final class KeyConverterController extends AbstractController {
	
	private $_inputKey;
	private $_convertedKey;
	private $_hashedKey;
	private $_iv;

    function __construct ($configPath = false) {
 		// AbstractController creates Config object from file path
		parent::__construct($configPath);
    	if (!extension_loaded('mcrypt') || !extension_loaded('hash') || !function_exists('mcrypt_create_iv')) {
    		$this->_setResponseError('Error: key conversion only works in PHP 7.0 or earlier');
    	}
    	$this->_inputKey = isset($_GET['key']) ? $_GET['key'] : false;
    	if (!$this->_inputKey) {
    		$this->_setResponseError('Error: key is missing');
    	}
        $this->_hashedKey = hash('sha256', $this->_config->getScrambleKey(), true);
        $this->_iv = mcrypt_create_iv(32, MCRYPT_DEV_URANDOM);
    }
    
    public function convertKey ($userMustExist = true) {
    	$userData = $this->_decrypt($this->_inputKey);
echo $this->_iv; die();
   		if (!is_array($userData) || count($userData) != 2) {
			list($userName, $hashedPassword) = $userData;
			// Check if user exists
			if ($userMustExist && !$this->_dbh->userLogin($userName, $hashedPassword)) {
				$this->_setResponseError('Error! User credentials incorrect');
				return $this->getResponse();
			}
			$this->_convertedKey = $this->_makeApiKey($userName, $hashedPassword);
		} else {
			$this->_setResponseError('Error! Incorrect key');
		}
		$this->getResponse();
    }
    
	public function getResponse () {
		if (!empty($this->_response->error)) {
			// Make sure no data is returned but the error itself;
			// re-initalise response with current error
			return $this->_initResponse($this->_response->error);
		}
		$this->_response->inputKey = $this->_inputKey;
		$this->_response->convertedKey = $this->_convertedKey;
		return $this->_response;
	}
    
	// CLone of RS method
    private function _decrypt ($input) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_hashedKey, 
        	base64_decode(strtr($input, '-_,', '+/=')), MCRYPT_MODE_ECB, $this->_iv));
    }
 


}