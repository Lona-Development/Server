<?php

namespace LonaDB\Plugins;

use LonaDB\LonaDB;
use LonaDB\Logger;

require 'vendor/autoload.php';

class PluginBase{
    private string $Name;
    private LonaDB $LonaDB;

    public function __construct(LonaDB $LonaDB, string $name) {
        $this->LonaDB = $LonaDB;
        $this->Name = $name;

        $this->GetLogger()->Load("Loading Plugin '" . $this->Name . "'");
    }

    public function onEnable() : void {
        $this->GetLogger()->Info("Plugin '" . $this->Name . "' has been loaded");
    }

    final public function GetLonaDB() : LonaDB { return $this->LonaDB; }

    final public function GetName() : string { return $this->Name; }

    final private function GetLogger() : Logger { return $this->LonaDB->Logger; } 
}