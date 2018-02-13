<?php

require_once 'Autoloader.php';

use RsApi\UserController as User;
// use RsApi\UploadController as Upload;

$rsConfigPath = '/Users/ruud/ETI/Zend workbenches/Current/ResourceSpace/include/config.php';

// Controllers MUST be initiased with path to RS config file!
$user = new User($rsConfigPath);
// var_dump($user->userExists('pipo'));
var_dump($user->createUser('henk'));






//$upload = new Upload($rsConfigPath);