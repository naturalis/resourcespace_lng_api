<?php

require_once '../../Autoloader.php';

// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';

// Controllers MUST be initiased with path to RS config file!
$upload = new RsApi\Upload($rsConfigPath);
$print = $upload
	->checkApiCredentials('cnxobWp_MWx1PD1wLWcyZ2A2PnZqY3ctNzIxOzA8JT4zKyE8YDNnOm4lPGB1KmNlMWVgPyQ4ZnZ-MTU1Nzo_dDEwICw3YA,,')
	->createUser(substr(uniqid('', true), -5));





header('Content-Type: application/json');
die($print);
