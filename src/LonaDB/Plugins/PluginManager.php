<?php

namespace LonaDB\Plugins;

use Phar;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafeArray;
use pmmp\thread\ThreadSafe;
use pmmp\thread\Thread;

class PluginManager extends ThreadSafe {
    private bool $loaded = false;
    private ThreadSafeArray $plugins;
    private LonaDB $lonaDB;

    /**
     * PluginManager constructor.
     * @param LonaDB $lonaDB The LonaDB instance
     */
    public function __construct(LonaDB $lonaDB) {
        $this->lonaDB = $lonaDB;
        $this->lonaDB->getLogger()->info("PluginManager initialized.");
        $this->plugins = new ThreadSafeArray();
    }

    /**
     * Load all plugins from the plugins folder
     */
    public function loadPlugins(): void {
        if($this->loaded) {
            $this->lonaDB->getLogger()->info("Plugins already loaded.");
            return;
        }

        $this->lonaDB->getLogger()->info("Loading plugins...");
        $pluginsLoaded = false;

        $pluginDir = $this->lonaDB->getBasePath() . "/plugins/";
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir);
        }
        $plugins = scandir($pluginDir);

        $this->lonaDB->getLogger()->info("Working with plugin folder $pluginDir");

        foreach ($plugins as $plugin) {
            if (str_ends_with($plugin, ".phar")){
                $this->lonaDB->getLogger()->info("Found plugin $plugin");
                $this->loadPluginPHAR($plugin);
                $pluginsLoaded = true;
            }
        }

        if(!$pluginsLoaded) {
            $this->lonaDB->getLogger()->info("No plugins found.");
        }
    }

    /**
     * Load a plugin from a PHAR file
     * @param string $plugin The plugin file name
     */
    private function loadPluginPHAR(string $plugin): void {
        $this->lonaDB->getLogger()->info("Loading plugin $plugin...");
        $pharPath = $this->lonaDB->getBasePath() . "/plugins/" . $plugin;
        $phar = new Phar($pharPath);

        // Load plugin.json as ThreadSafeArray
        $configPath = $phar->offsetGet("plugin.json")->getPathName();
        $pluginConfig = ThreadSafeArray::fromArray(json_decode(file_get_contents($configPath), true));

        if(    !$pluginConfig["main"]
            || !$pluginConfig["name"]
            || !$pluginConfig["version"]
            || !$pluginConfig["author"]) {
            $this->lonaDB->getLogger()->error("Plugin $plugin is missing required fields in plugin.json.");
            return;
        }

        // Load plugin classes
        $this->loadClasses($phar, $pluginConfig["main"]["class"]);

        // Create plugin instance
        $MainClassNamespace = $pluginConfig["main"]["namespace"];
        $mainClassName = $pluginConfig["main"]["class"];
        $mainClass = $MainClassNamespace . "\\" . $mainClassName;

        eval("\$pluginInstance = new {$mainClass}(\$this->lonaDB, \$pharPath, \"{$pluginConfig["main"]["class"]}\", \"{$pluginConfig["name"]}\", \"{$pluginConfig["version"]}\");");
        
        $this->plugins[$pluginConfig["name"]] = $pluginInstance;
        $this->lonaDB->getLogger()->info("Loaded plugin {$pluginConfig["name"]} v{$pluginConfig["version"]}");

        // Start plugin thread
        $pluginInstance->start(Thread::INHERIT_NONE);
    }

    /**
     * Load all classes from a PHAR file
     * @param Phar $phar The PHAR file
     * @param string $path The path to load classes from
     */
    private function loadClasses(Phar $phar, string $main, string $path = ""): void {
        if(!str_starts_with($path, "/")) $path = "/" . $path;
        else if($path === "") $path = "/";

        $content = scandir($phar->offsetGet("plugin.json")->getPath() . $path);
        foreach ($content as $file) {
            if ($file === "." || $file === "..") continue;
            if (is_dir($phar->offsetGet("plugin.json")->getPath() . $path . $file)) {
                $this->loadClasses($phar, $main, $path . $file . "/");
            } else {
                if(str_ends_with($file, ".php")){
                    if($file == $main . ".php") 
                        require $phar->offsetGet("plugin.json")->getPath() . "/" . $path . $file;
                }
            }
        }
    }

    public function getPlugin(string $name): ?Thread {
        return $this->plugins[$name] ?? null;
    }

    public function getPlugins(): ThreadSafeArray {
        return $this->plugins;
    }

    public function getPluginNames(): array {
        return array_keys($this->plugins->toArray());
    }

    public function isPluginLoaded(string $name): bool {
        return isset($this->plugins[$name]);
    }

    public function unloadPlugin(string $name): void {
        if($this->isPluginLoaded($name)) {
            $this->plugins[$name]->onDisable();
            $this->plugins[$name]->stop();
            unset($this->plugins[$name]);
        }
    }

    public function runEvent(string $executor, string $event, ThreadSafeArray $arguments): void {
        foreach ($this->plugins as $plugin) {
            $name = $arguments["name"];
            match ($event) {
                "table_create"      => $pluginInstance->onTableCreate($executor, $name),
                "table_delete"      => $pluginInstance->onTableDelete($executor, $name),
                "value_set"         => $pluginInstance->onValueSet($executor, $arguments['table'], $name, $arguments['value']),
                "value_remove"      => $pluginInstance->onValueRemove($executor, $arguments['table'], $name),
                "function_create"   => $pluginInstance->onFunctionCreate($executor, $name, $arguments['content']),
                "function_delete"   => $pluginInstance->onFunctionDelete($executor, $name),
                "function_execute"  => $pluginInstance->onFunctionExecute($executor, $name),
                "user_create"       => $pluginInstance->onUserCreate($executor, $name),
                "user_delete"       => $pluginInstance->onUserDelete($executor, $name),
                "eval"              => $pluginInstance->onEval($executor, $arguments['content']),
                "permission_add"    => $pluginInstance->onPermissionAdd($executor, $arguments['user'], $name),
                "permission_remove" => $pluginInstance->onPermissionRemove($executor, $arguments['user'], $name),
            };
        }
    }


}
