<?php

namespace LonaDB\Plugins;

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file
use LonaDB\LonaDB;

class PluginManager{
    //Create all variables
    private LonaDB $LonaDB;
    private array $Plugins;
    private array $EnabledPlugins;
    public bool $Loaded = false;
    private array $pids;

    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;
        //Initialize plugins array
        $this->Plugins = array();
    }

    public function LoadPlugins() : void {
        //Check if plugins have already been loaded
        if ($this->Loaded == true) return;
        $this->Loaded = true;
    
        //Check if "plugins/" directory exists, create if it doesn't
        if (!is_dir("plugins/")) mkdir("plugins/");
    
        //For all files and folders in "plugins/"
        $results = scandir("plugins/");
        foreach ($results as $result) {
            if ($result != "." && $result != "..")
                $this->LonaDB->Logger->Info("Plugins found: " . $result);
        }
    
        foreach ($results as $r) {
            //Check if file ends with ".phar" => Plugin has been compiled
            if (str_ends_with($r, ".phar")) {
                //Load PHAR file
                $phar = new \Phar("plugins/" . $r, 0);
                $configFound = false;
    
                //Loop through all files in the PHAR archive
                foreach (new \RecursiveIteratorIterator($phar) as $file) {
                    //Check for plugin.json
                    if ($file->getFileName() === "plugin.json") {
                        //Get configuration from plugin.json
                        $conf = json_decode(file_get_contents($file->getPathName()), true);
                        //Generate path variable for the file
                        eval("\$path = substr(\$file->getPathName(), 0, -" . strlen($file->getFileName()) . ");");
                        $configFound = true;
                    }
                }
    
                if ($configFound) {
                    //Check the configuration
                    if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                        //Check if main file declared in plugin.json exists
                        file_put_contents($path . $conf['main']['path'], file_get_contents($path . $conf['main']['path']));
                        if (file_get_contents($path . $conf['main']['path']) !== "") {
                            try {
                                //Load PHAR
                                $this->load_classphp($path, $phar);
    
                                //Add it to the Plugins array
                                eval("\$this->Plugins[\$conf['name']] = new " . $conf['main']['namespace'] . "\\" . $conf['main']['class'] . "(\$this->LonaDB, \$conf['name'], \$conf['version']);");
    
                                // Run plugin's onEnable event directly, no need for fork
                                $this->Plugins[$conf['name']]->onEnable();
                            } catch (Exception $e) {
                                $this->LonaDB->Logger->Error("Could not load main file for plugin '" . $conf['name'] . "'");
                            }
                        } else $this->LonaDB->Logger->Error("Main file for plugin '" . $conf['name'] . "' is declared in config but doesn't exist");
                    } else {
                        $this->LonaDB->Logger->Error("Could not load the plugin in '" . $r . "'");
                    }
                } else {
                    $this->LonaDB->Logger->Error("Missing config in '" . $r . "'");
                }
            }
            // Load plugin from folder => Plugin hasn't been compiled
            else if ($r != "." && $r !== "..") {
                //Scan "plugins/$folder"
                $debugscan = scandir("plugins/" . $r);
                $configFound = false;
                //Check if plugin.json is inside the folder
                if (in_array("plugin.json", $debugscan)) {
                    $conf = json_decode(file_get_contents("plugins/" . $r . "/plugin.json"), true);
                    $configFound = true;
                }
                if ($configFound) {
                    //Check configuration
                    if ($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']) {
                        //Check if main file exists
                        file_put_contents("plugins/" . $r . "/" . $conf['main']['path'], file_get_contents("plugins/" . $r . "/" . $conf['main']['path']));
                        if (file_get_contents("plugins/" . $r . "/" . $conf['main']['path']) !== "") {
                            try {
                                //Load all PHP files in the folder
                                $this->load_classphp("plugins/" . $r . "/");
    
                                //Add plugin to the plugins array
                                eval("\$this->Plugins[\$conf['name']] = new " . $conf['main']['namespace'] . "\\" . $conf['main']['class'] . "(\$this->LonaDB, \$conf['name'], \$conf['version']);");
    
                                // Run plugin's onEnable event directly, no need for fork
                                $this->Plugins[$conf['name']]->onEnable();
                            } catch (Exception $e) {
                                $this->LonaDB->Logger->Error("Could not load main file for plugin '" . $conf['name'] . "'");
                            }
                        } else $this->LonaDB->Logger->Error("Main file for plugin '" . $conf['name'] . "' is declared in config but doesn't exist");
                    } else {
                        $this->LonaDB->Logger->Error("Could not load the plugin in '" . $r . "'");
                    }
                } else {
                    $this->LonaDB->Logger->Error("Missing configuration for plugin in '" . $r . "'");
                }
            }
        }
    }
    

    public function KillThreads() : void {
        //Loop through all process IDs
        foreach($pid as $this->pids) {
            //Kill the thread
            posix_kill( $pid, SIGKILL );
        }
        $this->LonaDB->Logger->Info("Plugin threads have been killed");
    }

    public function GetPlugin(string $name) : mixed {
        //Return plugin instance of ot exists
        if($this->Plugins[$name]) return $this->Plugins[$name];
        else return false;
    }

    private function load_classphp(string $path, \Phar $phar = null) : void {
        //Check if loading from PHAR
        if(str_starts_with($path, "phar")){
            //Loop through PHAR
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                //Load file if its a PHP file
                if(str_ends_with($file->getPathName(), ".php")) require_once $file->getPathName();
            }
        }

        //Remove the last "/" if its the last character in the path name
        if(str_ends_with($path, "/")) $path = substr($path, 0, -1);
        //Scan directory
        $items = glob( $path . "/*" );
        //Loop through the directory
        foreach( $items as $item ) {
            //Check if file ends with PHP
            $isPhp = str_ends_with($item, ".php");
            
            if ( $isPhp ) {
                //Load file
                require_once $item;
            } else{
                //If its a folder, load PHP files inside
                $this->load_classphp( $item . "/" );
            }
        }
    }

    public function RunEvent(string $executor, string $event, Array $arguments) : void {
        if (!is_array($arguments)) {
            $this->LonaDB->Logger->Error("Invalid arguments provided for event: " . $event);
            return;
        }
        //Loop through all plugins
        foreach($this->Plugins as $pluginName => $pluginInstance) {
            //Run event identified by name
            switch($event){
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