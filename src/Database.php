<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Support\Facades\Artisan;
use PDO;
use RuntimeException;
use Illuminate\Support\Str;

class Database
{
    protected static $database = '';
    protected static $host = '';
    protected static $username = '';
    protected static $password = '';
    protected static $charset = 'utf8';
    protected static $collation = 'utf8_unicode_ci';
    protected static $instance = null;

    static public function createRandomDatabase()
    {
        if (static::$instance) {
            return static::$database;
        }

        $database = 'test_' . date('Ymd') . '_' . uniqid();

        static::$database = $database;
        static::$host = env('DB_HOST');
        static::$username = env('DB_USERNAME');
        static::$password = env('DB_PASSWORD');

        try {
            $dbh = new PDO('mysql:host=' . static::$host, static::$username, static::$password);
            $dbh->exec('CREATE DATABASE ' . $database);
        } catch (PDOException $e) {
            throw new RuntimeException('Connect Error: ' . $e->getMessage());
        }

        $dbh = null;
        static::$instance = new static();

        return $database;
    }

    static public function dropDatabase()
    {
        try {
            $dbh = new PDO('mysql:host=' . static::$host, static::$username, static::$password);
            $sql = 'DROP DATABASE ' . static::$database;
            $today = date('Ymd');
            foreach ($dbh->query('SHOW DATABASES') as $row) {
                if (Str::startsWith($row['Database'], 'test_')) {
                    $date = explode('_', $row['Database'])[1];
                    if ($today > $date) {
                        $sql .= '; DROP DATABASE ' . $row['Database'];
                    }
                }
            }
            $dbh->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException('Connect Error: ' . $e->getMessage());
        }

        $dbh = null;
    }

    public function __destruct()
    {
        static::dropDatabase();
    }
}
