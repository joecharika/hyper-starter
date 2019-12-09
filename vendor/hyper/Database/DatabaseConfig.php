<?php


namespace Hyper\Database;


use Hyper\Application\HyperApp;
use Hyper\Functions\Obj;

class DatabaseConfig
{
    public $host,
        $database,
        $username,
        $password = '';

    public function __construct(string $host = null, string $database = null, string $username = null, string $password = null)
    {
        $db = Obj::property(
            HyperApp::config(),
            'db',
            (object)[
                'database' => null,
                'host' => null,
                'username' => null,
                'password' => null
            ]
        );

        $this->database = $database ?? $db->database;
        $this->host = $host ?? $db->host;
        $this->username = $username ?? $db->username;
        $this->password = $password ?? $db->password;

        $db = null;
    }

}