<?php

require_once '../../Autoloader.php';
use RsApi\UploadController as Upload;

// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';

// Controllers MUST be initiased with path to RS config file!
$upload = new Upload($rsConfigPath);


