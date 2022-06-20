<?php

namespace fileManager;

use exceptions\FileExistsException;
use exceptions\NotExistsException;

class Directory
{
    /**
     * @throws FileExistsException
     */
    public static function make(string $path): bool
    {
        if (file_exists($path)) {
            throw new FileExistsException("Directory \"{$path}\" already exists");
        }

        return mkdir($path, 0700);
    }

    /**
     * @throws NotExistsException
     */
    public static function cd(string $path): bool
    {
        if (!file_exists($path)) {
            throw new NotExistsException("Directory \"{$path}\" is not exists");
        }

        return chdir($path);
    }
}