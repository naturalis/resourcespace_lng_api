<?php

/*
 * This hint will save you hours of frustration!
 * 
 * ResourceSpace has two functions to create api keys
 * (in include/general.php). On the Naturalis RS servers,
 * the mcrypt extension was NOT loaded. This is all too
 * well, as mcrypt was removed from PHP 7.2.
 * 
 * The current api emulates the alternate option in the 
 * make_api_key() and decrypt_api_key() methods. When 
 * testing on a platform that DOES support mcrypt, **make
 * sure to modify the methods above** to display the
 * correct api key in Admin interface! 
 */

require_once '../../Autoloader.php';

// Path to RS config file
require_once '../../config.php';

// Controllers MUST be initiased with path to RS config file!
$user = new RsApi\UserController($rsConfigPath);
$user->createUser();

// Response is json-formatted
header('Content-Type: application/json');
die(json_encode($user->getResponse()));