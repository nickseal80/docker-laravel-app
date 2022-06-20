<?php

include "log/Console.php";
include "fileManager/Directory.php";
include "fileManager/File.php";
include "Builder.php";
include "exceptions/FileExistsException.php";
include "exceptions/NotExistsException.php";
include "exceptions/FileException.php";
include "exceptions/RequiredFieldException.php";
include "config.php";

use app\Builder;

parse_str($argv[1], $output);

$builder = new Builder($config);
$builder->run($output['path'], $output['appName'], $output['sourcePath']);
