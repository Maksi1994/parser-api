<?php

namespace App\Controllers;

use App\Models\WorldLocationParserModel;
use Composer\Script\Event;

class WorldLocationParserController
{

    public static function runParser(Event $event)
    {   $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        define('DOCUMENT_ROOT', str_replace('/vendor', '', $vendorDir));

        require $vendorDir . '/autoload.php';

        $worldLocationParserModel = new WorldLocationParserModel();
        $worldLocationParserModel->runParser();
}


}