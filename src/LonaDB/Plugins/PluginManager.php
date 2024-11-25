<?php

namespace LonaDB\Plugins;

require 'vendor/autoload.php';

use Exception;
use LonaDB\LonaDB;
use Phar;
use RecursiveIteratorIterator;

class PluginManager
{
    private LonaDB $lonaDB;
    private array $plugins;
    public bool $loaded = false;
    private array $pids;

    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        //Initialize plugins array
        $this->plugins = array();
    }

    public function loadPlugins(): void
    {
        //Check if plugins have already been loaded
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        //Check if the "plugins/" directory exists, create if it doesn't
        if (!is_dir("plugins/")) {
            mkdir("plugins/");
        }

        //For all files and folders in "plugins/"
        $results = scandir("plugins/");
        foreach ($results as $result) {
            if ($result != "." && $result != "..") {
                $this->lonaDB->logger->info("Plugins found: ".$result);
            }
        }

        foreach ($results as $r) {
            //Check if a file ends with ".phar" => Plugin has been compiled
            if (str_ends_with($r, ".phar")) {
                //Load a PHAR file
                $phar = new Phar("plugins/".$r, 0);
                $configFound = false;

                //Loop through all files in the PHAR archive
                foreach (new RecursiveIteratorIterator($phar) as $file) {
                    //Check for plugin.json
                    if ($file->getFileName() === "plugin.json") {
                        //Get configuration from plugin.json
                        $conf = json_decode(file_get_contents($file->getPathName()), true);
                        //Generate path variable for the file
                        eval("\$path = substr(\$file->getPathName(), 0, -".strlen($file->getFileName()).");");
                        $configFound = true;
                    }
                }

                if ($configFound) {
                    //Check the configuration
                    if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                        //Check if the main file declared in plugin.json exists
                        file_put_contents($path.$conf['main']['path'], file_get_contents($path.$conf['main']['path']));
                        if (file_get_contents($path.$conf['main']['path']) !== "") {
                            try {
                                //Load PHAR
                                $this->load_classphp($path, $phar);

                                //Add it to the Plugins array
                                eval("\$this->Plugins[\$conf['name']] = new ".$conf['main']['namespace']."\\".$conf['main']['class']."(\$this->LonaDB, \$conf['name'], \$conf['version']);");

                                // Run plugin onEnable event directly, no need for fork
                                $this->plugins[$conf['name']]->onEnable();
                            } catch (Exception $e) {
                                $this->lonaDB->logger->Error("Could not load main file for plugin '".$conf['name']."'");
                            }
                        } else {
                            $this->lonaDB->logger->Error("Main file for plugin '".$conf['name']."' is declared in config but doesn't exist");
                        }
                    } else {
                        $this->lonaDB->logger->Error("Could not load the plugin in '".$r."'");
                    }
                } else {
                    $this->lonaDB->logger->Error("Missing config in '".$r."'");
                }
            } // Load plugin from folder => Plugin hasn't been compiled
            else {
                if ($r != "." && $r !== "..") {
                    //Scan "plugins/$folder"
                    $debugScan = scandir("plugins/".$r);
                    $configFound = false;
                    //Check if plugin.json is inside the folder
                    if (in_array("plugin.json", $debugScan)) {
                        $conf = json_decode(file_get_contents("plugins/".$r."/plugin.json"), true);
                        $configFound = true;
                    }
                    if ($configFound) {
                        //Check configuration
                        if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                            //Check if the main file exists
                            file_put_contents("plugins/".$r."/".$conf['main']['path'],
                                file_get_contents("plugins/".$r."/".$conf['main']['path']));
                            if (file_get_contents("plugins/".$r."/".$conf['main']['path']) !== "") {
                                try {
                                    //Load all PHP files in the folder
                                    $this->load_classphp("plugins/".$r."/");

                                    //Add a plugin to the plugin array
                                    eval("\$this->plugins[\$conf['name']] = new ".$conf['main']['namespace']."\\".$conf['main']['class']."(\$this->lonaDB, \$conf['name'], \$conf['version']);");

                                    // Run plugin onEnable event directly, no need for fork
                                    $this->plugins[$conf['name']]->onEnable();
                                } catch (Exception $e) {
                                    $this->lonaDB->logger->error("Could not load main file for plugin '".$conf['name']."'");
                                }
                            } else {
                                $this->lonaDB->logger->error("Main file for plugin '".$conf['name']."' is declared in config but doesn't exist");
                            }
                        } else {
                            $this->lonaDB->logger->error("Could not load the plugin in '".$r."'");
                        }
                    } else {
                        $this->lonaDB->logger->error("Missing configuration for plugin in '".$r."'");
                    }
                }
            }
        }
    }


    public function killThreads(): void
    {
        //Loop through all process IDs
        foreach ($pid as $this->pids) {
            //Kill the thread
            posix_kill($pid, SIGKILL);
        }
        $this->lonaDB->Logger->Info("Plugin threads have been killed");
    }

    public function getPlugin(string $name): bool
    {
        return $this->plugins[$name] ?? false;
    }

    private function load_classphp(string $path, Phar $phar = null): void
    {
        //Check if loading from PHAR
        if (str_starts_with($path, "phar")) {
            //Loop through PHAR
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                //Load a file if it's a PHP file
                if (str_ends_with($file->getPathName(), ".php")) {
                    require_once $file->getPathName();
                }
            }
        }

        //Remove the last "/" if its last character in the path name
        if (str_ends_with($path, "/")) {
            $path = substr($path, 0, -1);
        }
        //Scan directory
        $items = glob($path."/*");
        //Loop through the directory
        foreach ($items as $item) {
            //Check if a file ends with PHP
            $isPhp = str_ends_with($item, ".php");

            if ($isPhp) {
                //Load file
                require_once $item;
            } else {
                //If it's a folder, load PHP files inside
                $this->load_classphp($item."/");
            }
        }
    }

    public function RunEvent(string $executor, string $event, array $arguments): void
    {
        if (!is_array($arguments)) {
            $this->lonaDB->logger->Error("Invalid arguments provided for event: ".$event);
            return;
        }
        //Loop through all plugins
        foreach ($this->plugins as $pluginInstance) {
            //Run event identified by name
            switch ($event) {
                case "tableCreate":
                    $pluginInstance->onTableCreate($executor, $arguments['name']);
                    break;
                case "tableDelete":
                    $pluginInstance->onTableDelete($executor, $arguments['name']);
                    break;
                case "valueSet":
                    $pluginInstance->onValueSet($executor, $arguments['name'], $arguments['value']);
                    break;
                case "valueRemove":
                    $pluginInstance->onValueRemove($executor, $arguments['name']);
                    break;
                case "functionCreate":
                    $pluginInstance->onFunctionCreate($executor, $arguments['name'], $arguments['content']);
                    break;
                case "functionDelete":
                    $pluginInstance->onFunctionDelete($executor, $arguments['name']);
                    break;
                case "functionExecute":
                    $pluginInstance->onFunctionExecute($executor, $arguments['name']);
                    break;
                case "userCreate":
                    $pluginInstance->onUserCreate($executor, $arguments['name']);
                    break;
                case "userDelete":
                    $pluginInstance->onUserDelete($executor, $arguments['name']);
                    break;
                case "eval":
                    $pluginInstance->onEval($executor, $arguments['content']);
                    break;
                case "permissionAdd":
                    $pluginInstance->onPermissionAdd($executor, $arguments['user'], $arguments['permission']);
                    break;
                case "permissionRemove":
                    $pluginInstance->onPermissionRemove($executor, $arguments['user'], $arguments['permission']);
                    break;
            }
        }
    }
}