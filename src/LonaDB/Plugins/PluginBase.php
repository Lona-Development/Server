<?php

namespace LonaDB\Plugins;

require '../../vendor/autoload.php';

use Phar;
use LonaDB\LonaDB;
use LonaDB\Logger;
use pmmp\thread\Thread;
use pmmp\thread\ThreadSafeArray;

class PluginBase extends Thread
{
    private LonaDB $lonaDB;
    private string $phar;
    private string $main;
    private string $name;
    private string $version;

    /**
     * Constructor for the PluginBase class.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param string $name The name of the plugin.
     * @param string $version The version of the plugin.
     */
    public function __construct(LonaDB $lonaDB, string $phar, string $main, string $name, string $version)
    {
        $this->lonaDB = $lonaDB;
        $this->name = $name;
        $this->phar = $phar;
        $this->main = $main;
        $this->version = $version;
    }

    /**
     * Load all classes from a PHAR file
     * @param Phar $phar The PHAR file
     * @param string $path The path to load classes from
     */
    private function loadClasses(Phar $phar, string $path = ""): void {
        if(!str_starts_with($path, "/")) $path = "/" . $path;
        else if($path === "") $path = "/";

        $content = scandir($phar->offsetGet("plugin.json")->getPath() . $path);
        foreach ($content as $file) {
            if ($file === "." || $file === "..") continue;
            if (is_dir($phar->offsetGet("plugin.json")->getPath() . $path . $file)) {
                $this->loadClasses($phar, $path . $file . "/");
            } else {
                if(str_ends_with($file, ".php")){
                    if($file !== $this->main . ".php") 
                        require($phar->offsetGet("plugin.json")->getPath() . $path . $file);
                }
            }
        }
    }

   /**
     * Gets the LonaDB instance.
     *
     * @return LonaDB The LonaDB instance.
     */
    final public function getLonaDB(): LonaDB
    {
        return $this->lonaDB;
    }

    /**
     * Gets the Logger instance.
     *
     * @return Logger The Logger instance.
     */
    final public function getLogger(): Logger
    {
        return $this->lonaDB->getLogger();
    }

    /**
     * Gets the Phar instance.
     *
     * @return Phar The plugin's Phar instance.
     */
    final public function getPhar(): Phar
    {
        return new Phar($this->phar);
    }

    /**
     * Gets the name of the plugin.
     *
     * @return string The name of the plugin.
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the version of the plugin.
     *
     * @return string The version of the plugin.
     */
    final public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Runs the plugin as a thread.
     */
    final public function run(): void
    {
        error_reporting(E_ERROR | E_PARSE);
        require __DIR__ . '/../../vendor/autoload.php';
        $this->loadClasses($this->getPhar());
        $this->onEnable();
    }

    /**
     * Event triggered when the plugin is enabled.
     */
    public function onEnable(): void
    {
        $this->getLogger()->info("Plugin '".$this->name."' has been loaded");
    }

    /**
     * Event triggered when a table is created.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the table.
     */
    public function onTableCreate(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when a table is deleted.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the table.
     */
    public function onTableDelete(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when a value is set in a table.
     *
     * @param string $executor The executor of the action.
     * @param string $table The name of the table.
     * @param string $name The name of the variable.
     * @param string $value The value being set.
     */
    public function onValueSet(string $executor, string $table, string $name, string $value): void
    {
    }

    /**
     * Event triggered when a value is removed from a table.
     *
     * @param string $executor The executor of the action.
     * @param string $table The name of the table.
     * @param string $name The name of the variable.
     */
    public function onValueRemove(string $executor, string $table, string $name): void
    {
    }

    /**
     * Event triggered when a function is created.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the function.
     * @param string $content The content of the function.
     */
    public function onFunctionCreate(string $executor, string $name, string $content): void
    {
    }

    /**
     * Event triggered when a function is deleted.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the function.
     */
    public function onFunctionDelete(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when a function is executed.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the function.
     */
    public function onFunctionExecute(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when a user is created.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the user.
     */
    public function onUserCreate(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when a user is deleted.
     *
     * @param string $executor The executor of the action.
     * @param string $name The name of the user.
     */
    public function onUserDelete(string $executor, string $name): void
    {
    }

    /**
     * Event triggered when an eval command is executed.
     *
     * @param string $executor The executor of the action.
     * @param string $content The content of the eval command.
     */
    public function onEval(string $executor, string $content): void
    {
    }

    /**
     * Event triggered when a permission is added to a user.
     *
     * @param string $executor The executor of the action.
     * @param string $user The name of the user.
     * @param string $permission The permission being added.
     */
    public function onPermissionAdd(string $executor, string $user, string $permission): void
    {
    }

    /**
     * Event triggered when a permission is removed from a user.
     *
     * @param string $executor The executor of the action.
     * @param string $user The name of the user.
     * @param string $permission The permission being removed.
     */
    public function onPermissionRemove(string $executor, string $user, string $permission): void
    {
    }

}
