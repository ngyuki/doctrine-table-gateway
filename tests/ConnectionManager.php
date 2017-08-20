<?php
namespace ngyuki\TableGateway\Test;

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
            $conn = DriverManager::getConnection(
                self::getConfig() + [
                    'driver' => 'pdo_mysql',
                    'driverOptions' => [],
                ]
            );
        }

        return $conn;
    }
}
