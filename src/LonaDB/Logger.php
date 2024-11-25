<?php

namespace LonaDB;

//Load autoload from composer
require 'vendor/autoload.php';

//Load the Main file
use LonaDB\LonaDB;

class Logger{
    //Create all variables
    private $logFile;
    private string $infoCache = "";
    private bool $start = false;

    private LonaDB $lonaDB;

    public function __construct(LonaDB $lonaDB){
        $this->lonaDB = $lonaDB;
    }

    private function log(string $message, string $color = "") : void {
        echo($color.$message."\e[0m");
        //Write logs to file if enabled
        if($this->lonaDB->config["logging"]) fwrite($this->logFile, $message);
    }

    public function loadLogger() : void {
        //Create a file instance if logging to file is enabled
        if($this->lonaDB->config["logging"]) $this->logFile = fopen('log.txt','a');
    }

    public function start($msg) : void {
        //Check if the Startup message has already been sent
        if(!$this->start){
            //Declare that the Startup message has been sent
            $this->start = true;
            
            //Send the Startup message
            $log = date("Y-m-d h:i:s")." [Startup] ".$msg."\n";
            $this->log($log);
        }
    }

    public function infoCache($msg) : void {
        //Log InfoCache into the terminal
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        echo($log);

        //Add a message to the InfoCache variable
        $this->infoCache = $this->infoCache . $log;
    }

    public function dropCache() : void {
        //Drop all of InfoCache's content into the log file if enabled
        if($this->lonaDB->config["logging"]) fwrite($this->logFile, $this->infoCache);
    }

    //Logger functions
    public function warning($msg) : void {
        $log = date("Y-m-d h:i:s")." [WARNING] ".$msg."\n";
        $this->log($log, "\033[33m");
    }

    public function error($msg) : void {
        $log = date("Y-m-d h:i:s")." [ERROR] ".$msg."\n";
        $this->log($log, "\033[31m");
    }

    public function create($msg) : void {
        $log = date("Y-m-d h:i:s")." [CREATE] ".$msg."\n";
        $this->log($log, "\033[32m");
    }

    public function load($msg) : void {
        $log = date("Y-m-d h:i:s")." [LOAD] ".$msg."\n";
        $this->log($log);
    }

    public function info($msg) : void {
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        $this->log($log, "\033[34m");
    }

    public function table($msg) : void {
        $log = date("Y-m-d h:i:s")." [TABLE] ".$msg."\n";
        $this->log($log);
    }

    public function user($msg) : void {
        $log = date("Y-m-d h:i:s")." [USER] ".$msg."\n";
        $this->log($log);
    }

    public function plugin($name, $msg) : void {
        $log = date("Y-m-d h:i:s")." [Plugin] ".$name.": ".$msg."\n";
        $this->log($log,"\033[35m");
    }
}