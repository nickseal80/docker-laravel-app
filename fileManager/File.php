<?php

namespace fileManager;

use exceptions\FileException;
use exceptions\IncorrectFileNameException;

class File
{
    /**
     * @throws FileException
     */
    public static function createFile(string $fileName, string $contents): void
    {
//        if (!preg_match('/\.\w+$/', $fileName)) {
//            throw new IncorrectFileNameException("The file does not contain an extension");
//        }

        $fd = fopen($fileName, 'w') or throw new FileException("File creation error");
        fputs($fd, $contents);
        fclose($fd);
    }

    public static function readFile(string $fileName): bool|string
    {
        return file_get_contents($fileName);
    }
}
