<?php

require_once '../../Autoloader.php';

// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';
$key = 'IjEwMS1lLXAkJDs3dThlMywpLXApc25kIDZhNy0pJXNyJmwyITM1NScoICIiLG49IzgxYCF7JHAidmg2JWJnZnYpInEmIA,,';

// Controllers MUST be initiased with path to RS config file!
$user = new RsApi\UserController($rsConfigPath);
if ($user->checkApiCredentials($key, false)) {
	$user->createUser(substr(uniqid('', true), -5));
}



header('Content-Type: application/json');
die(json_encode($user->getuserData()));


