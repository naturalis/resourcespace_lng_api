<?php

namespace RsApi;

require_once 'AbstractController.php';

final class UploadController extends AbstractController {

	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	public function checkApiCredentials ($apiKey = false) {
		if (!$apiKey) {
			$this->_setUserDataError('Error! No api key provided');
		} else if (!$this->_checkApiCredentials($apiKey)) {
			$this->_setUserDataError('Error! Incorrect api key provided');
		}
		return $this;
	}
	
	
	
}