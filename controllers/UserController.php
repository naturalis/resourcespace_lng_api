<?php

/**
 * Creates new RS user and returns relevant data in json format
 * 
 * This class MUST be initialised with a path to the RS config file.
 * The config file contains the database credentials plus hash keys 
 * for api access and password obfuscation.
 * 
 * NB: RS admin credentials are required to create a new user!
 * 
 * Required data:
	$_GET['key'] = 'abcdef123456'; // RS api key
	$_GET['newuser'] = 'User Name'; // user name
 */

namespace RsApi;

final class UserController extends AbstractController {
	
	private $_userId;
	private $_userName;
	private $_userPassword;
	private $_hashedUserPassword;
	private $_collectionId;

	public function __construct ($configPath = false) {
		// AbstractController creates Config object from file path
		parent::__construct($configPath);
		// Initialise model with Config object
		$this->_dbh = new UserModel($this->_config);
		if (isset($_GET['newuser'])) {
			$this->_userName = $_GET['newuser'];
		}
	}
		
	public function userExists ($name = null) {
		if (!empty($name)) {
			return $this->_dbh->userExists($name);
		}
		return false;
	}
	
	// Controller specific: user must be superadmin to add another user!
	public function checkApiCredentials () {
		if (!isset($_GET['key'])) {
			$this->_setResponseError('Error! No api key provided');
			return $this->getResponse();
		// User MUST be admin to create new users!
		} else if (!$this->_checkApiCredentials($_GET['key'], true)) {
			$this->_setResponseError('Error! Incorrect api key provided');
			return $this->getResponse();
		}
		return $this;
	}
	
	public function createUser ($name = null) {
		if (!is_null($name)) {
			$this->_userName = $name;
		}
		$this->checkApiCredentials();
		if (!$this->_loginSuccessful) {
		    return $this->getResponse();
		// User name not provided (neither through url not passed directly)
		} else if (empty($this->_userName)) {
			$this->_setResponseError("Error! No user name provided for new user");
		} else if (!$this->userExists($name)) {
			$this->_createUserPassword();
			$this->_userId = $this->_dbh->createUser($this->_userName);
			if (!empty($this->_userId)) {
				$this->_dbh->createNewUserDash($this->_userId);
				$this->_collectionId = $this->_dbh->createCollection($this->_userId);
				$this->_dbh->saveUserData($this->_userName, $this->_userId, $this->_hashedUserPassword);
			}
		} else {
			$this->_setResponseError("User $name already exists!");
		}
		return $this->getResponse();
	}
	
	// Formatted response
	public function getResponse () {
		if (!empty($this->_response->error)) {
			// Make sure no data is returned but the error itself;
			// re-initalise response with current error
			return $this->_initResponse($this->_response->error);
		}
		$this->_response->username = $this->_userName;
		$this->_response->user_id = $this->_userId;
		$this->_response->password = $this->_userPassword;
		$this->_response->collection_id = $this->_collectionId;
		$this->_response->authentification_key = 
			$this->_makeApiKey($this->_userName, $this->_hashedUserPassword);	
		return $this->_response;
	}
	
	private function _createUserPassword () {
		$this->_userPassword = bin2hex(openssl_random_pseudo_bytes(4));
		$this->_hashedUserPassword = hash('sha256', md5('RS' . $this->_userName . $this->_userPassword));
	}
}