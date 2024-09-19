<?php

namespace LonaDB\Plugins;

use LonaDB\LonaDB;
use LonaDB\Logger;

require 'vendor/autoload.php';

class PluginBase{
    private LonaDB $LonaDB;
    private string $Name;
    private string $Version;

    public function __construct(LonaDB $LonaDB, string $name, string $version) {
        $this->LonaDB = $LonaDB;
        $this->Name = $name;
        $this->Version = $version;

        $this->GetLogger()->Load("Loading Plugin '" . $this->Name . "'");
    }

    public function onEnable() : void {
        $this->GetLogger()->Info("Plugin '" . $this->Name . "' has been loaded");
    }

    final public function GetLonaDB() : LonaDB { return $this->LonaDB; }

    final public function GetLogger() : Logger { return $this->LonaDB->Logger; }

    final public function GetName() : string { return $this->Name; }

    final public function GetVersion() : string { return $this->Version; }

    //Events
    public function onTableCreate(string $executor, string $name) : void {}
    public function onTableDelete(string $executor, string $name) : void {}
    public function onValueSet(string $executor, string $name, string $value) : void {}
    public function onValueRemove(string $executor, string $name) : void {}
    public function onFunctionCreate(string $executor, string $name, string $content) : void {}
    public function onFunctionDelete(string $executor, string $name) : void {}
    public function onFunctionExecute(string $executor, string $name) : void {}
    public function onUserCreate(string $executor, string $name) : void {}
    public function onUserDelete(string $executor, string $name) : void {}
    public function onEval(string $executor, string $content) : void {}
    public function onPermissionAdd(string $executor, string $user, string $permission) : void {}
    public function onPermissionRemove(string $executor, string $user, string $permission) : void {}
    
}