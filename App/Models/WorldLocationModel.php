<?php
namespace App\Models;

use App\Tools\DataBaseConnection;


class WorldLocationModel extends DataBaseConnection {

    public function __construct()
    {
        $this->connectDataBase();
    }
}