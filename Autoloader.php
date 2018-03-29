<?php

// Assumes Autoloader is located at root
define('BASE_PATH', realpath(dirname(__FILE__)));
define('CLASS_DIRS', serialize(['models', 'controllers'])); // php 5 hack

class Autoloader
{
    public static function register () {
        spl_autoload_register(function ($class) {
        	foreach (unserialize(CLASS_DIRS) as $dir) {
        		// Trim namespace from class name
        		$parts = explode('\\', $class);
    			$class = end($parts);
    			// Now we can create the correct path to check
	            $file = BASE_PATH . '/' . $dir . '/' . 
	            	str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
	            // Load class if file is present and class actually exists
	            if (file_exists($file)) {
	                require_once $file;
	                return true;
	            } 
        	}
            return false;
        });
    }
}

Autoloader::register();