#!/bin/bash

path=`readlink -f "${BASH_SOURCE:-$0}"`

DIR_PATH=`dirname $path`

php -f index.php -- "path=$1&appName=$2&sourcePath=$DIR_PATH"