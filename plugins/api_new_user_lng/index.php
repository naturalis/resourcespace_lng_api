<?php

require_once '../../Autoloader.php';

// Path to RS config file
require_once '../../config.php';

// Controllers MUST be initiased with path to RS config file!
$user = new RsApi\UserController($rsConfigPath);
$user->createUser();

// Response is json-formatted
header('Content-Type: application/json');
die(json_encode($user->getResponse()));