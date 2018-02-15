<?php

require_once 'Autoloader.php';

use RsApi\UserController as User;
// use RsApi\UploadController as Upload;

$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';

// Controllers MUST be initiased with path to RS config file!
$user = new User($rsConfigPath);
$user->setMasterKey('cnxobWp_MWx1PD1wLWcyZ2A2PnZqY3ctNzIxOzA8JT4zKyE8YDNnOm4lPGB1KmNlMWVgPyQ4ZnZ-MTU1Nzo_dDEwICw3YA,,');

// var_dump($user->userExists('pipo'));
die( $user->createUser('harry'));





//$upload = new Upload($rsConfigPath);
