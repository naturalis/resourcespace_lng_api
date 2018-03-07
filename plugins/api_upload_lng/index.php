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

/*
 * 
 * stdClass Object
(
    [collection] => 2
    [resource] => stdClass Object
        (
            [score] => 0
            [ref] => 1829
            [resource_type] => 1
            [has_image] => 1
            [is_transcoding] => 0
            [hit_count] => 0
            [creation_date] => 2018-02-27 15:51:34
            [rating] => 
            [user_rating] => 
            [user_rating_count] => 
            [user_rating_total] => 
            [file_extension] => png
            [preview_extension] => jpg
            [image_red] => 61
            [image_green] => 926
            [image_blue] => 891
            [thumb_width] => 150
            [thumb_height] => 97
            [archive] => 0
            [access] => 0
            [colour_key] => KEBNW
            [created_by] => 2
            [file_modified] => 2018-02-27 15:51:35
            [file_checksum] => 
            [request_count] => 0
            [new_hit_count] => 0
            [expiry_notification_sent] => 0
            [preview_tweaks] => 0|1
            [file_path] => 
            [group_access] => 
            [user_access] => 
            [field12] => 
            [field8] => No title
            [field51] => prince.png
            [field3] => 
            [Contributed_by] => Euphausiids of the World Ocean 1.1 @ 145.136.240.190
            [files] => Array
                (
                    [0] => stdClass Object
                        (
                            [name] => High resolution print
                            [width] => 642
                            [height] => 415
                            [extension] => png
                            [src] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829_0dba64cee1594f8.png?v=2018-02-27+15%3A51%3A34
                        )

                    [1] => stdClass Object
                        (
                            [name] => High resolution print
                            [width] => 642
                            [height] => 415
                            [extension] => jpg
                            [src] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829hpr_7e90ba99283f454.jpg?v=2018-02-27+15%3A51%3A34
                        )

                    [2] => stdClass Object
                        (
                            [name] => Screen
                            [width] => 642
                            [height] => 415
                            [extension] => jpg
                            [src] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829scr_2a86c52e3cd5f63.jpg?v=2018-02-27+15%3A51%3A34
                        )

                )

            [thumbnails] => stdClass Object
                (
                    [small] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829col_bac2a88722a2b88.jpg?v=2018-02-27+15%3A51%3A35
                    [medium] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829thm_9017caaf5ac718a.jpg?v=2018-02-27+15%3A51%3A35
                    [large] => https://sandbox-rs-002.naturalis.nl/filestore/1/8/2/9_d8c2ee5ba581a8f/1829pre_a6e21bda5192941.jpg?v=2018-02-27+15%3A51%3A35
                )

        )

    [error] => 
)

 */


// Test data!
$_FILES['userfile'] = [
	'name' => 'prince.png',
	'type' => 'image/png',
	'tmp_name' => '/Users/ruud/Desktop/prince.png',
	'error' => 0,
	'size' => 34842
];
$_GET['field8'] = 'henk';
$_GET['collection'] = 1;


// Path to RS config file
$rsConfigPath = '/Users/ruud/Documents/MAMP/htdocs/resourcespace/include/config.php';

// Normal -- test
$key = 'a3NyMiR_ZVZZcEM1W3Y5OGkqJCB1Jzw3Izk3NyQtIyAnJj8xJWc8MCUhISMmIDtnIWQ3NXF7LCJydz4yIzA3NSwsI3cjdD1jImUxMC0qdnYi';
// Admin -- test
//$key = 'JjEzNCFlIyYjJmtgdThgZycvcnMjcWw8cDdgY3R6JCMlJj5hJ2IyY3R9cCIjIDxjJzU0MiV6J3FzIjhgK2c8N3MpJy0gJw,,';

// Controllers MUST be initiased with path to RS config file!
$upload = new RsApi\UploadController($rsConfigPath);
$upload
	->checkApiCredentials($key)
	->createResource();

header('Content-Type: application/json');
die(json_encode($upload->getResponse()));
