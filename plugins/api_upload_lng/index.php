<?php

require_once '../../Autoloader.php';

// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';

// Controllers MUST be initiased with path to RS config file!
$upload = new RsApi\UploadController($rsConfigPath);
$upload->createResource();

// Response is json-formatted
header('Content-Type: application/json');
die(json_encode($upload->getResponse()));
