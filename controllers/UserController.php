<?php

namespace RsApi;

require_once 'AbstractController.php';
require_once 'models/UserModel.php';

final class UserController extends AbstractController {
	
	private $_userId;
	private $_userName;
	private $_userPassword;
	private $_hashedUserPassword;
	private $_collectionId;

	public function __construct ($config = false) {
		parent::__construct($config);
		$this->_db = new UserModel($this->_config);
	}
	
	public function userExists ($name = null) {
		if (!empty($name)) {
			return $this->_db->userExists($name);
		}
		return false;
	}
	
	public function createUser ($name = null) {
		if (!$this->userExists($name)) {
			$this->_userName = $name;
			$this->_createUserPassword();
			$this->userId = $this->_db->createUser($this->_userName);
			if (!empty($this->userId)) {
				$this->_db->createNewUserDash($this->userId);
				$this->_collectionId = $this->_db->createCollection($this->userId);
				$this->_db->saveUserData($this->_userName, $this->userId, $this->_hashedUserPassword);
			}
		}
		return null;
	}
	
	private function _createUserPassword () {
		$this->_userPassword = bin2hex(openssl_random_pseudo_bytes(4));
		$this->_hashedUserPassword = hash('sha256', md5('RS' . $this->_userName . $this->_userPassword));
	}
	
	private function _makeApiKey () {
	    if (extension_loaded('mcrypt') && extension_loaded('hash')) {
	        $cipher = new \Cipher($this->_config->rsApiScrambleKey);
	        return $cipher->encrypt($this->_userName . "|" . $this->_hashedUserPassword, 
	        	$this->_config->rsApiScrambleKey);
	    } else {
	        return strtr(base64_encode($this->_convert($this->_userName . "|" . $this->_hashedUserPassword, 
	        	$this->_config->rsApiScrambleKey)), '+/=', '-_,');
		}		
	}

}