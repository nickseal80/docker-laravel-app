<?php

namespace app;

use Exception;
use exceptions\FileException;
use exceptions\FileExistsException;
use exceptions\NotExistsException;
use fileManager\Directory;
use fileManager\File;
use log\Console;

class Builder
{
    private string $appRootPath;
    private string $workingDir;
    private string $internalWorkingDir;
    private array $config;
    private string $sourcePath;

    protected int $appExternalPort;
    protected int $mysqlExternalPort;
    protected string $mysqlDatabase;
    protected string $mysqlRootPassword;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function run(string $path, string $appName, $sourcePath): void
    {
        $this->workingDir = $path;
        $this->sourcePath = $sourcePath;

        $this->cloneLaravelRepository($path, $appName);
        $this->installComposer();
        $this->createDockerComposeFile();
        $this->createDockerfile();
        $this->configPhpSettings();
        $this->configNginxSettings();
        $this->configMysqlSettings();
        $this->createENV();

        try {
            Directory::cd($this->appRootPath);
            shell_exec("docker-compose build");
            shell_exec("docker-compose up -d");

            shell_exec("docker-compose exec app php artisan key:generate");
            shell_exec("docker-compose exec app php artisan config:cache");

            Console::log("Installation completed successfully", Console::COLOR_SUCCESS);
            Console::log("The project is available at the link http://localhost:$this->appExternalPort");
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function cloneLaravelRepository(string $path, $appName): void
    {
        Console::log("Cloning laravel repository ... ", Console::COLOR_STD_OUT);
        try {
            $this->appRootPath = $path . '/' . $appName;
            Directory::cd($path);
            shell_exec("git clone https://github.com/laravel/laravel.git {$this->appRootPath}");
            Directory::cd($this->appRootPath);
        } catch (NotExistsException) {
            $create = Console::ask(
                "Directory \"$path\" is not exists. Create it? Y/N ",
                Console::COLOR_ASK
            );
            if (strtoupper($create) === 'Y') {
                $this->createAppDirectory($path);
                Console::log("Cloning laravel repository ... ", Console::COLOR_STD_OUT);
            } else {
                Console::log("Build process stopped", Console::COLOR_ERROR);
            }
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function installComposer(): void
    {
        Console::log("installing the composer ... ", Console::COLOR_STD_OUT);
        shell_exec("docker run --rm -v $(pwd):/app composer install");
        shell_exec('sudo chown -R $USER:$USER ' . $this->appRootPath);
        Console::log("... DONE", Console::COLOR_SUCCESS);
    }

    public function createDockerComposeFile(): void
    {
        $this->internalWorkingDir = $this->getWorkingDir();
        if ($this->internalWorkingDir === (string)$this->config['workingDirectory']) {
            Console::log(
                "Default working directory " . $this->config['workingDirectory'] . " selected",
                Console::COLOR_STD_OUT
            );
        }

        $this->appExternalPort = $this->getAppExternalPort();
        if ($this->appExternalPort === (int)$this->config['appExternalPort']) {
            Console::log(
                "Default application port " . $this->config['appExternalPort'] . " selected",
                Console::COLOR_STD_OUT
            );
        }

        $this->mysqlExternalPort = $this->getMysqlExternalPort();
        if ($this->mysqlExternalPort === (int)$this->config['mysqlExternalPort']) {
            Console::log(
                "Default mysql port " . $this->config['mysqlExternalPort'] . " selected",
                Console::COLOR_STD_OUT
            );
        }

        $this->mysqlDatabase = $this->getDatabaseName();
        $this->mysqlRootPassword = $this->getMysqlPassword();

        Console::log("Creating the docker-compose file ... ", Console::COLOR_STD_OUT, false);

        $contents = $this->prepareDockerComposeTemplate();

        try {
            Directory::cd($this->appRootPath);
            File::createFile('docker-compose.yml', $contents);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (Exception $exception) {
            Console::log("Can't create a docker-compose.yml file", Console::COLOR_ERROR);
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function createDockerfile(): void
    {
        Console::log("Creating the Dockerfile ... ", Console::COLOR_STD_OUT, false);

        $contents = $this->prepareDockerfileTemplate();

        try {
            Directory::cd($this->appRootPath);
            File::createFile('Dockerfile', $contents);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (Exception $exception) {
            Console::log("Can't create a Dockerfile", Console::COLOR_ERROR);
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function configPhpSettings(): void
    {
        Console::log("Configure php settings ... ", Console::COLOR_STD_OUT, false);
        $dirname = $this->appRootPath . '/php';
        try {
            Directory::make($dirname);
            Directory::cd($dirname);
            File::createFile("local.ini", $this->config['settings']['php']);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (FileExistsException) {
            $continue = Console::ask(
                "Directory \"$dirname\" already exists. Continue? Y/N ",
                Console::COLOR_ASK
            );

            if (strtoupper($continue) !== 'Y') {
                Console::log("Installation stopped", Console::COLOR_ERROR);
                return;
            }
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function configNginxSettings(): void
    {
        Console::log("Configure Nginx settings ... ", Console::COLOR_STD_OUT, false);
        $dirname = $this->appRootPath . '/nginx/conf.d';
        try {
            Directory::make("$this->appRootPath/nginx");
            Directory::make("$this->appRootPath/nginx/conf.d");
            Directory::cd($dirname);
            $contents = File::readFile("$this->sourcePath/templates/nginx-config-template.php");
            File::createFile("app.conf", $contents);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (FileExistsException) {
            $continue = Console::ask(
                "Directory \"$dirname\" already exists. Continue? Y/N ",
                Console::COLOR_ASK
            );

            if (strtoupper($continue) !== 'Y') {
                Console::log("Installation stopped", Console::COLOR_ERROR);
                return;
            }
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function configMysqlSettings(): void
    {
        Console::log("Configure MySQL settings ... ", Console::COLOR_STD_OUT, false);
        $dirname = "$this->appRootPath/mysql";
        try {
            Directory::make($dirname);
            Directory::cd($dirname);
            File::createFile("my.cnf", $this->config['settings']['mysql']);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (FileExistsException) {
            $continue = Console::ask(
                "Directory \"$dirname\" already exists. Continue? Y/N ",
                Console::COLOR_ASK
            );

            if (strtoupper($continue) !== 'Y') {
                Console::log("Installation stopped", Console::COLOR_ERROR);
                return;
            }
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    public function createENV(): void
    {
        Console::log("Creating .env file ... ", Console::COLOR_STD_OUT, false);
        $contents = File::readFile("$this->sourcePath/templates/base-env-template.php");
        $contents = str_replace("{! dbName !}", $this->mysqlDatabase, $contents);
        $contents = str_replace("{! mysqlPassword !}", $this->mysqlRootPassword, $contents);
        try {
            Directory::cd($this->appRootPath);
            File::createFile(".env", $contents);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    private function createAppDirectory(string $path): void
    {
        Console::log("Creating application directory ... ", "yellow", false);
        try {
            Directory::make($path);
            Console::log("OK", Console::COLOR_SUCCESS);
        } catch (FileExistsException $exception) {
            Console::log("exists", Console::COLOR_STD_OUT);
            $continue = Console::ask(
                "Directory \"{$path}\" already exists. Continue? Y/N ",
                Console::COLOR_ASK
            );
            if (strtoupper($continue) === 'Y') {
                // callback?
            } else {
                Console::log("Process stopped", Console::COLOR_ERROR);
            }
        } catch (Exception $exception) {
            Console::log($exception->getMessage(), Console::COLOR_ERROR);
        }
    }

    private function getWorkingDir(): string
    {
        $dir = Console::ask(
            "Specify working directory (" . $this->config['workingDirectory'] . ") ",
            Console::COLOR_ASK
        );
        if ($dir === '') {
            return $this->config['workingDirectory'];
        }

        return $dir;
    }

    private function getAppExternalPort(): int
    {
        $port = Console::ask(
            "Specify application external port (" . $this->config['appExternalPort'] . ") ",
            Console::COLOR_ASK
        );
        if ($port === '') {
            $port = $this->config['appExternalPort'];
        }

        return (int)$port;
    }

    private function getMysqlExternalPort(): int
    {
        $port = Console::ask(
            "Specify mysql external port (" . $this->config['mysqlExternalPort'] . ") ",
            Console::COLOR_ASK
        );
        if ($port === '') {
            $port = $this->config['mysqlExternalPort'];
        }

        return (int)$port;
    }

    private function getDatabaseName(): string
    {
        $database = Console::ask("Database name: ", Console::COLOR_ASK);
        if ($database === '') {
            Console::log("Database name is required", Console::COLOR_ERROR);
            $this->getDatabaseName();
        }

        return $database;
    }

    private function getMysqlPassword(): string
    {
        $password = Console::askPassword("Mysql root password: ", Console::COLOR_ASK);
        if (!$this->validatePassword($password)) {
            $this->getMysqlPassword();
        }
        $confirmPasswd = Console::askPassword("Confirm mysql root password: ", Console::COLOR_ASK);
        if ((string)$password !== (string)$confirmPasswd) {
            $message = "Password and confirmation do not match. Please enter your password again and confirm it";
            Console::log($message, Console::COLOR_ERROR);
            $this->getMysqlPassword();
        }

        return $password;
    }

    private function validatePassword(string $password): bool
    {
        if ($password === '') {
            Console::log("Mysql password is required", Console::COLOR_ERROR);

            return false;
        }
        if (strlen($password) < (int)$this->config['mysqlPasswordMin']) {
            Console::log(
                "Mysql password length must not be less than " . $this->config['mysqlPasswordMin'] . " character",
                Console::COLOR_ERROR
            );

            return false;
        }

        return true;
    }

    private function prepareDockerComposeTemplate(): string
    {
        $template = File::readFile("$this->sourcePath/templates/docker-compose-template.php");
        $template = str_replace('{! workingDir !}', $this->internalWorkingDir, $template);
        $template = str_replace('{! appExternalPort !}', $this->appExternalPort, $template);
        $template = str_replace('{! mysqlExternalPort !}', $this->mysqlExternalPort, $template);
        $template = str_replace('{! mysqlDatabase !}', $this->mysqlDatabase, $template);
        return str_replace('{! mysqlRootPassword !}', $this->mysqlRootPassword, $template);
    }

    private function prepareDockerfileTemplate(): string
    {
        $template = File::readFile("$this->sourcePath/templates/dockerfile-template.php");
        return str_replace('{! workingDir !}', $this->internalWorkingDir, $template);
    }
}
