<?php
namespace App\Tools;

class DataBaseConnection
{
    protected function connectDataBase()
    {
        $root = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : DOCUMENT_ROOT;
        $json = file_get_contents("$root/.env/environment.json");
        $objParams = json_decode($json, true);
        $db = $objParams['db'];

        return new \PDO("mysql:dbname={$db['name']};host={$db['host']}", $db['user'], $db['password']);
    }
}