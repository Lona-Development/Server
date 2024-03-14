<?php

namespace LonaDB\Plugins;

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class PluginManager{
    private LonaDB $LonaDB;
    private array $Plugins;
    private array $EnabledPlugins;
    public bool $Loaded = false;
    private array $pids;

    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;
        $this->Tables = array();
    }

    public function LoadPlugins () : void {
        $this->Loaded = true;
        if(!is_dir("plugins/")) mkdir("plugins/");

        $results = scandir("plugins/");

        foreach($results as $r){
            if(str_ends_with($r, ".phar")){
                $phar = new \Phar("plugins/" . $r, 0);

                foreach (new \RecursiveIteratorIterator($phar) as $file) {
                    if($file->getFileName() === "plugin.json") {
                        $conf = json_decode(file_get_contents($file->getPathName()), true);
                        eval("\$path = substr(\$file->getPathName(), 0, -". strlen($file->getFileName()) .");");
                    }
                }

                if($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']){
                    file_put_contents($path . $conf['main']['path'], file_get_contents($path . $conf['main']['path']));
                    if(file_get_contents($path. $conf['main']['path']) !== ""){
                        try{
                            $this->load_classphp($path, $phar);
    
                            eval("\$this->Plugins[\$conf['name']] = new " . $conf['main']['namespace'] . "\\" . $conf['main']['class'] . "(\$this->LonaDB, \$conf['name']);");

                            $pid = @ pcntl_fork();
                            if( $pid == -1 ) {
                                throw new Exception( $this->getError( Thread::COULD_NOT_FORK ), Thread::COULD_NOT_FORK );
                            }
                            if( $pid ) {
                                $this->pids[$conf['name']] = $pid;
                            }
                            else {
                                $this->Plugins[$conf['name']]->onEnable();
                            }
                        }
                        catch(e){
                            $this->LonaDB->Logger->Error("Could not load main file for plugin '" . $conf['name'] . "'");
                        }
                    }
                    else $this->LonaDB->Logger->Error("Main file for plugin '" . $conf['name'] . "' is declared in config but doesn't exist");
                }
                else{
                    $this->LonaDB->Logger->Error("Could not load the plugin in '" . $r . "'");
                }
            }
            else if($r != "." && $r !== ".."){
                $debugscan = scandir("plugins/" . $r);

                if(in_array("plugin.json", $debugscan)) $conf = json_decode(file_get_contents("plugins/" . $r . "/plugin.json"), true);

                if($conf['main'] && $conf['main']['path'] && $conf['main']['class'] && $conf['main']['namespace'] && $conf['name']){
                    file_put_contents("plugins/" . $r . "/" . $conf['main']['path'], file_get_contents("plugins/" . $r . "/" . $conf['main']['path']));
                    if(file_get_contents("plugins/" . $r . "/" . $conf['main']['path']) !== ""){
                        try{
                            $this->load_classphp("plugins/" . $r . "/");
                            
                            if($conf['build']){
                                $phar = new \Phar("plugins/".$conf['name']."-".$conf['version'].".phar", 0, "plugins/".$conf['name']."-".$conf['version'].".phar");
            
                                $phar->buildFromDirectory("plugins/".$r."/");
                            
                                $phar->setDefaultStub($conf['main']['namespace'].'/'.$conf['main']['class'].'.php', $conf['main']['namespace'].'/'.$conf['main']['class'].'.php');
                            
                                $phar->setAlias($conf['name']."-".$conf['version'].".phar");
                            
                                $phar->stopBuffering();
                            }

                            eval("\$this->Plugins[\$conf['name']] = new " . $conf['main']['namespace'] . "\\" . $conf['main']['class'] . "(\$this->LonaDB, \$conf['name']);");

                            $pid = @ pcntl_fork();
                            if( $pid == -1 ) {
                                throw new Exception( $this->getError( Thread::COULD_NOT_FORK ), Thread::COULD_NOT_FORK );
                            }
                            if( $pid ) {
                                $this->pids[$conf['name']] = $pid;
                            }
                            else {
                                $this->Plugins[$conf['name']]->onEnable();
                            }
                        }
                        catch(e){
                            $this->LonaDB->Logger->Error("Could not load main file for plugin '" . $conf['name'] . "'");
                        }
                    }
                    else $this->LonaDB->Logger->Error("Main file for plugin '" . $conf['name'] . "' is declared in config but doesn't exist");
                }
                else{
                    $this->LonaDB->Logger->Error("Could not load the plugin in '" . $r . "'");
                }
            }
        }
    }

    public function KillThreads() : void {
        foreach($pid as $this->pids) {
            posix_kill( $pid, SIGKILL );
        }

        $this->LonaDB->Logger->Info("Plugin threads have been killed");
    }

    public function GetPlugin(string $name) : mixed {
        if($this->Plugins[$name]) return $this->Plugins[$name];
        else return false;
    }

    private function load_classphp(string $path, \Phar $phar = null) : void {
        if(str_starts_with($path, "phar")){
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                if(str_ends_with($file->getPathName(), ".php")) require_once $file->getPathName();
            }
        }
        if(str_ends_with($path, "/")) $path = substr($path, 0, -1);
        $items = glob( $path . "/*" );
        
        foreach( $items as $item ) {
            $isPhp = str_ends_with($item, ".php");
    
            if ( $isPhp ) {
                require_once $item;
            } else{
                $this->load_classphp( $item . "/" );
            }
        }
    }
}