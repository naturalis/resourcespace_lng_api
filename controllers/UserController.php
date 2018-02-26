<?php

namespace RsApi;

final class UserController extends AbstractController {
	
	private $_userId;
	private $_userName;
	private $_userPassword;
	private $_hashedUserPassword;
	private $_collectionId;
	private $_userData;

	public function __construct ($configPath = false) {
		// AbstractController creates Config object from file path
		parent::__construct($configPath);
		// Initialise model with Config object
		$this->_dbh = new UserModel($this->_config);
		// Create object that will be returned to Linnaeus
		$this->_initUserData();
	}
		
	public function userExists ($name = null) {
		if (!empty($name)) {
			return $this->_dbh->userExists($name);
		}
		return false;
	}
	
	public function checkApiCredentials ($apiKey = false) {
		if (!$apiKey) {
			$this->_setUserDataError('Error! No api key provided');
		// User MUST be admin to create new users!
		} else if (!$this->_checkApiCredentials($apiKey, true)) {
			$this->_setUserDataError('Error! Incorrect api key provided');
		}
		return $this;
	}
	
	public function createUser ($name = null) {
		if (!$this->_loginSucccessful) {
			$this->_setUserDataError("Error! Login failed");
		} else if (!$this->userExists($name)) {
			$this->_userName = $name;
			$this->_createUserPassword();
			$this->_userId = $this->_dbh->createUser($this->_userName);
			if (!empty($this->_userId)) {
				$this->_dbh->createNewUserDash($this->_userId);
				$this->_collectionId = $this->_dbh->createCollection($this->_userId);
				$this->_dbh->saveUserData($this->_userName, $this->_userId, $this->_hashedUserPassword);
			}
		} else {
			$this->_setUserDataError("User $name already exists!");
		}
		return $this->getUserData();
	}
	
	public function getUserData () {
		if (!empty($this->_userData->error)) {
			// Make sure no data is returned but the error itself
			unset($this->_userData->user_id, $this->_userData->password,
				$this->_userData->collection_id, $this->_userData->authentification_key);
			return $this->_userData;
		}
		$this->_userData->user_name = $this->_userName;
		$this->_userData->user_id = $this->_userId;
		$this->_userData->password = $this->_userPassword;
		$this->_userData->collection_id = $this->_collectionId;
		$this->_userData->authentification_key = $this->_makeApiKey();	
		return $this->_userData;
	}
	
	private function _createUserPassword () {
		$this->_userPassword = bin2hex(openssl_random_pseudo_bytes(4));
		$this->_hashedUserPassword = hash('sha256', md5('RS' . $this->_userName . $this->_userPassword));
	}
	
	private function _initUserData () {
		$this->_userData = new \stdClass();
		$this->_userData->error = null;
	}
	
	private function _makeApiKey () {
        return strtr(base64_encode($this->_convert($this->_userName . "|" . $this->_hashedUserPassword, 
        	$this->_config->getRsApiScrambleKey())), '+/=', '-_,');
	}
	
	private function _setUserDataError ($error) {
		$this->_userData->error = $error;
		return $this->_userData;
	}
	
}