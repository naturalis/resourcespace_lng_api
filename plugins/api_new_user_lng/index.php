<?php

require_once '../../Autoloader.php';

/*
 * admin:
 * 
 * admin
 * 3012ccbf
 * cdd3607eef3237b2f7048d82e8036d5021db3ffc6ec1493adc3dc34c68e67055
 * QkdoQlpdX29-VEdvWnY5OGl6cHEjIz0ydmRiNicqI3ciczo1JzlgPSd8LCUjI2kwIzM1YXcqcnNzI2hmIjU9NnR9dyZ0dj4xcDc8YCMuJCAl
 */



// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';


// Normal -- test
// $key = 'a3NyMiR_ZVZZcEM1W3Y5OGkqJCB1Jzw3Izk3NyQtIyAnJj8xJWc8MCUhISMmIDtnIWQ3NXF7LCJydz4yIzA3NSwsI3cjdD1jImUxMC0qdnYi';
// Admin -- test
$key = 'QkdoQlpdX29-VEdvWnY5OGl6cHEjIz0ydmRiNicqI3ciczo1JzlgPSd8LCUjI2kwIzM1YXcqcnNzI2hmIjU9NnR9dyZ0dj4xcDc8YCMuJCAl';

// Controllers MUST be initiased with path to RS config file!
$user = new RsApi\UserController($rsConfigPath);
$user	
	->checkApiCredentials($key)
	->createUser(base64_encode(random_bytes(10)));

header('Content-Type: application/json');
die(json_encode($user->getResponse()));