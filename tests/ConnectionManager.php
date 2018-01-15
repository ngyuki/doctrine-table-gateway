<?php
namespace ngyuki\DoctrineTableGateway\Test;

use Doctrine\DBAL\DriverManager;

class ConnectionManager
{
    public static function getConfig()
    {
        $files = [
            __DIR__ . '/config.php',
            __DIR__ . '/config.php.dist',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                /** @noinspection PhpIncludeInspection */
                return require $file;
            }
        }

        return [];
    }

    public static function getConnection()
    {
        static $conn;

        if (isset($conn) === false) {
            $config = self::getConfig() + [
                'driver' => 'pdo_mysql',
                'driverOptions' => [],
            ];
            $conn = DriverManager::getConnection($config);
            $conn->connect();
        }

        return $conn;
    }
}
