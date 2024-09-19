<?php

namespace LonaDB;

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file
use LonaDB\LonaDB;

class Logger{
    //Create all variables
    private $LogFile;
    private string $infoCache = "";
    private bool $Start = false;

    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
    }

    private function log(string $message, string $color = "") : void {
        echo($color.$message."\e[0m");
        //Write logs to file if enabled
        if($this->LonaDB->config["logging"]) fwrite($this->LogFile, $message);
    }

    public function LoadLogger() : void {
        //Create file instance if logging to file is enabled
        if($this->LonaDB->config["logging"]) $this->LogFile = fopen('log.txt','a');
    }

    public function Start($msg) : void {
        //Check if the Startup message has already been sent
        if(!$this->Start){
            //Declare that the Startup message has been sent
            $this->Start = true;
            
            //Send the Startup message
            $log = date("Y-m-d h:i:s")." [Startup] ".$msg."\n";
            $this->log($log);
        }
    }

    public function InfoCache($msg) : void {
        //Log InfoCache into the terminal
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        echo($log);

        //Add message to the InfoCache variable
        $this->infoCache = $this->infoCache . $log;
    }

    public function DropCache() : void {
        //Drop all of InfoCache's content into the log file if enabled
        if($this->LonaDB->config["logging"]) fwrite($this->LogFile, $this->infoCache);
    }

    //Logger funcitons
    public function Warning($msg) : void {
        $log = date("Y-m-d h:i:s")." [WARNING] ".$msg."\n";
        $this->log($log, "\033[33m");
    }

    public function Error($msg) : void {
        $log = date("Y-m-d h:i:s")." [ERROR] ".$msg."\n";
        $this->log($log, "\033[31m");
    }

    public function Create($msg) : void {
        $log = date("Y-m-d h:i:s")." [CREATE] ".$msg."\n";
        $this->log($log, "\033[32m");
    }

    public function Load($msg) : void {
        $log = date("Y-m-d h:i:s")." [LOAD] ".$msg."\n";
        $this->log($log);
    }

    public function Info($msg) : void {
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        $this->log($log, "\033[34m");
    }

    public function Table($msg) : void {
        $log = date("Y-m-d h:i:s")." [TABLE] ".$msg."\n";
        $this->log($log);
    }

    public function User($msg) : void {
        $log = date("Y-m-d h:i:s")." [USER] ".$msg."\n";
        $this->log($log);
    }

    public function Plugin($name, $msg) : void {
        $log = date("Y-m-d h:i:s")." [Plugin] ".$name.": ".$msg."\n";
        $this->log($log,"\033[35m");
    }
}