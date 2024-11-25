<?php

namespace LonaDB\Plugins;

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file and Logger class
use LonaDB\LonaDB;
use LonaDB\Logger;

class PluginBase
{
    //Create all variables
    private LonaDB $lonaDB;
    private string $name;
    private string $version;

    public function __construct(LonaDB $lonaDB, string $name, string $version)
    {
        $this->lonaDB = $lonaDB;
        $this->name = $name;
        $this->version = $version;

        $this->getLogger()->load("Loading Plugin '".$this->name."'");
    }

    //Get LonaDB instance
    final public function getLonaDB(): LonaDB
    {
        return $this->lonaDB;
    }

    //Get Logger instance
    final public function getLogger(): Logger
    {
        return $this->lonaDB->logger;
    }

    //Get own plugin name
    final public function getName(): string
    {
        return $this->name;
    }

    //Get an own plugin version
    final public function getVersion(): string
    {
        return $this->version;
    }

    //Events
    public function onEnable(): void
    {
        $this->getLogger()->Info("Plugin '".$this->name."' has been loaded");
    }

    public function onTableCreate(string $executor, string $name): void
    {
    }

    public function onTableDelete(string $executor, string $name): void
    {
    }

    public function onValueSet(string $executor, string $table, string $name, string $value): void
    {
    }

    public function onValueRemove(string $executor, string $table, string $name): void
    {
    }

    public function onFunctionCreate(string $executor, string $name, string $content): void
    {
    }

    public function onFunctionDelete(string $executor, string $name): void
    {
    }

    public function onFunctionExecute(string $executor, string $name): void
    {
    }

    public function onUserCreate(string $executor, string $name): void
    {
    }

    public function onUserDelete(string $executor, string $name): void
    {
    }

    public function onEval(string $executor, string $content): void
    {
    }

    public function onPermissionAdd(string $executor, string $user, string $permission): void
    {
    }

    public function onPermissionRemove(string $executor, string $user, string $permission): void
    {
    }

}