<?php
namespace App\Models;

use App\Tools\DataBaseConnection;

class PostModel extends DataBaseConnection {

    public function __construct()
    {
        $this->connectDataBase();
    }
}