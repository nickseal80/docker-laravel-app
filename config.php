<?php

$config = [
    'workingDirectory' => '/var/www',
    'appExternalPort' => 8080,
    'mysqlExternalPort' => 3306,
    'mysqlPasswordMin' => 7,
    'internalWorkingDirectory' => '/var/www',

    'settings' => [
        'php' => "upload_max_filesize=40M\npost_max_size=40M",
        'mysql' => "[mysqld]\ngeneral_log = 1\ngeneral_log_file = /var/lib/mysql/general.log",
    ]
];
