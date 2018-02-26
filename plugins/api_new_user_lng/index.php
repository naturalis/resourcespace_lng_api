<?php

require_once '../../Autoloader.php';

// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';


// Normal -- test
$key = 'IjEwMS1lLXAkJDs3dThlMywpLXApc25kIDZhNy0pJXNyJmwyITM1NScoICIiLG49IzgxYCF7JHAidmg2JWJnZnYpInEmIA,,';
// Admin -- test
$key = 'JjEzNCFlIyYjJmtgdThgZycvcnMjcWw8cDdgY3R6JCMlJj5hJ2IyY3R9cCIjIDxjJzU0MiV6J3FzIjhgK2c8N3MpJy0gJw,,';

// Controllers MUST be initiased with path to RS config file!
$user = new RsApi\UserController($rsConfigPath);
$user	
	->checkApiCredentials($key)
	->createUser(base64_encode(random_bytes(10)));

header('Content-Type: application/json');
die(json_encode($user->getuserData()));


