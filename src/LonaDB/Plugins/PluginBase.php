<?php

namespace LonaDB\Plugins;

require 'vendor/autoload.php';

use LonaDB\LonaDB;
use LonaDB\Logger;

class PluginBase
{
    private LonaDB $lonaDB;
    private string $name;
    private string $version;

    /**
     * Constructor for the PluginBase class.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param string $name The name of the plugin.
     * @param string $version The version of the plugin.
     */
    public function __construct(LonaDB $lonaDB, string $name, string $version)
    {
        $this->lonaDB = $lonaDB;
        $this->name = $name;
        $this->version = $version;

        $this->getLogger()->load("Loading Plugin '".$this->name."'");
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
        return $this->lonaDB->logger;
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
     * Event triggered when the plugin is enabled.
     */
    public function onEnable(): void
    {
        $this->getLogger()->Info("Plugin '".$this->name."' has been loaded");
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