<?php

namespace LonaDB\Plugins;

require 'vendor/autoload.php';

use Exception;
use LonaDB\Enums\Event;
use LonaDB\LonaDB;
use Phar;
use RecursiveIteratorIterator;

class PluginManager
{
    private LonaDB $lonaDB;
    private array $plugins;
    public bool $loaded = false;
    private array $pids;

    /**
     * Constructor for the PluginManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        // Initialize plugins array
        $this->plugins = array();
    }

    /**
     * Loads all plugins from the "plugins/" directory.
     */
    public function loadPlugins(): void
    {
        // Check if plugins have already been loaded
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        // Check if the "plugins/" directory exists, create if it doesn't
        if (!is_dir("plugins/")) {
            mkdir("plugins/");
        }

        // For all files and folders in "plugins/"
        $results = scandir("plugins/");
        foreach ($results as $result) {
            if ($result != "." && $result != "..") {
                $this->lonaDB->getLogger()->info("Plugins found: ".$result);
            }
        }

        foreach ($results as $r) {
            // Check if a file ends with ".phar" => Plugin has been compiled
            if (str_ends_with($r, ".phar")) {
                // Load a PHAR file
                $phar = new Phar("plugins/".$r, 0);
                $configFound = false;

                // Loop through all files in the PHAR archive
                foreach (new RecursiveIteratorIterator($phar) as $file) {
                    // Check for plugin.json
                    if ($file->getFileName() === "plugin.json") {
                        // Get configuration from plugin.json
                        $conf = json_decode(file_get_contents($file->getPathName()), true);
                        // Generate path variable for the file
                        eval("\$path = substr(\$file->getPathName(), 0, -".strlen($file->getFileName()).");");
                        $configFound = true;
                    }
                }

                if ($configFound) {
                    // Check the configuration
                    if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                        // Check if the main file declared in plugin.json exists
                        file_put_contents($path.$conf['main']['path'], file_get_contents($path.$conf['main']['path']));
                        if (file_get_contents($path.$conf['main']['path']) !== "") {
                            try {
                                // Load PHAR
                                $this->load_classphp($path, $phar);

                                // Add it to the Plugins array
                                eval("\$this->plugins[\$conf['name']] = new ".$conf['main']['namespace']."\\".$conf['main']['class']."(\$this->lonaDB, \$conf['name'], \$conf['version']);");

                                // Run plugin onEnable event directly, no need for fork
                                $this->plugins[$conf['name']]->onEnable();
                            } catch (Exception $e) {
                                $this->lonaDB->getLogger()->error("Could not load main file for plugin '".$conf['name']."'");
                            }
                        } else {
                            $this->lonaDB->getLogger()->error("Main file for plugin '".$conf['name']."' is declared in config but doesn't exist");
                        }
                    } else {
                        $this->lonaDB->getLogger()->error("Could not load the plugin in '".$r."'");
                    }
                } else {
                    $this->lonaDB->getLogger()->error("Missing config in '".$r."'");
                }
            } // Load plugin from folder => Plugin hasn't been compiled
            else {
                if ($r != "." && $r !== "..") {
                    // Scan "plugins/$folder"
                    $debugScan = scandir("plugins/".$r);
                    $configFound = false;
                    // Check if plugin.json is inside the folder
                    if (in_array("plugin.json", $debugScan)) {
                        $conf = json_decode(file_get_contents("plugins/".$r."/plugin.json"), true);
                        $configFound = true;
                    }
                    if ($configFound) {
                        // Check configuration
                        if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                            // Check if the main file exists
                            file_put_contents("plugins/".$r."/".$conf['main']['path'],
                                file_get_contents("plugins/".$r."/".$conf['main']['path']));
                            if (file_get_contents("plugins/".$r."/".$conf['main']['path']) !== "") {
                                try {
                                    // Load all PHP files in the folder
                                    $this->load_classphp("plugins/".$r."/");

                                    // Check if the plugin should be built
                                    if ($conf['build']) {
                                        // Build the PHAR
                                        $phar = new \Phar("plugins/".$conf['name']."-".$conf['version'].".phar", 0,
                                            "plugins/".$conf['name']."-".$conf['version'].".phar");
                                        $phar->buildFromDirectory("plugins/".$r."/");
                                        $phar->setDefaultStub($conf['main']['namespace'].'/'.$conf['main']['class'].'.php',
                                            $conf['main']['namespace'].'/'.$conf['main']['class'].'.php');
                                        $phar->setAlias($conf['name']."-".$conf['version'].".phar");
                                        $phar->stopBuffering();
                                    }

                                    // Add a plugin to the plugin array
                                    eval("\$this->plugins[\$conf['name']] = new ".$conf['main']['namespace']."\\".$conf['main']['class']."(\$this->lonaDB, \$conf['name'], \$conf['version']);");

                                    // Run plugin onEnable event directly, no need for fork
                                    $this->plugins[$conf['name']]->onEnable();
                                } catch (Exception $e) {
                                    $this->lonaDB->getLogger()->error("Could not load main file for plugin '".$conf['name']."'");
                                }
                            } else {
                                $this->lonaDB->getLogger()->error("Main file for plugin '".$conf['name']."' is declared in config but doesn't exist");
                            }
                        } else {
                            $this->lonaDB->getLogger()->error("Could not load the plugin in '".$r."'");
                        }
                    } else {
                        $this->lonaDB->getLogger()->error("Missing configuration for plugin in '".$r."'");
                    }
                }
            }
        }
    }

    /**
     * Kills all plugin threads.
     */
    public function killThreads(): void
    {
        // Loop through all process IDs
        foreach ($pid as $this->pids) {
            // Kill the thread
            posix_kill($pid, SIGKILL);
        }
        $this->lonaDB->getLogger()->Info("Plugin threads have been killed");
    }

    /**
     * Retrieves a plugin by name.
     *
     * @param  string  $name  The name of the plugin.
     * @return bool Returns the plugin instance if found, false otherwise.
     */
    public function getPlugin(string $name): bool
    {
        return $this->plugins[$name] ?? false;
    }

    /**
     * Loads PHP classes from the specified path.
     *
     * @param  string  $path  The path to load classes from.
     * @param  Phar|null  $phar  The PHAR archive to load classes from, if applicable.
     */
    private function load_classphp(string $path, Phar $phar = null): void
    {
        // Check if loading from PHAR
        if (str_starts_with($path, "phar")) {
            // Loop through PHAR
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                if (str_ends_with($file->getPathName(), ".php")) {
                    require_once $file->getPathName();
                }
            }
        }

        if (str_ends_with($path, "/")) {
            $path = substr($path, 0, -1);
        }
        $items = glob($path."/*");
        foreach ($items as $item) {
            str_ends_with($item, ".php") ? require_once $item : $this->load_classphp($item."/");
        }
    }

    /**
     * Runs an event for all plugins.
     *
     * @param  string  $executor  The executor of the action.
     * @param  string  $event  The name of the event.
     * @param  array  $arguments  The arguments to pass to the event.
     */
    public function runEvent(string $executor, Event $event, array $arguments): void
    {
        if (!is_array($arguments)) {
            $this->lonaDB->getLogger()->error("Invalid arguments provided for event: ".$event);
            return;
        }
        foreach ($this->plugins as $pluginInstance) {
            $name = $arguments['name'];
            match ($event->value) {
                "tableCreate" => $pluginInstance->onTableCreate($executor, $name),
                "tableDelete" => $pluginInstance->onTableDelete($executor, $name),
                "valueSet" => $pluginInstance->onValueSet($executor, $arguments['table'], $name, $arguments['value']),
                "valueRemove" => $pluginInstance->onValueRemove($executor, $arguments['table'], $name),
                "functionCreate" => $pluginInstance->onFunctionCreate($executor, $name, $arguments['content']),
                "functionDelete" => $pluginInstance->onFunctionDelete($executor, $name),
                "functionExecute" => $pluginInstance->onFunctionExecute($executor, $name),
                "userCreate" => $pluginInstance->onUserCreate($executor, $name),
                "userDelete" => $pluginInstance->onUserDelete($executor, $name),
                "eval" => $pluginInstance->onEval($executor, $arguments['content']),
                "permissionAdd" => $pluginInstance->onPermissionAdd($executor, $arguments['user'], $name),
                "permissionRemove" => $pluginInstance->onPermissionRemove($executor, $arguments['user'], $name),
            };
        }
    }
}