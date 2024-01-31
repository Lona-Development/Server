<?php

namespace LonaDB;

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class Logger{
    private $LogFile;
    private string $infoCache = "";
    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
    }

    private function log(string $message){
        echo($message);
        if($this->LonaDB->config["logging"]) fwrite($this->LogFile, $message);
    }

    public function LoadLogger(){
        if($this->LonaDB->config["logging"]) $this->LogFile = fopen('log.txt','a');
    }

    public function Warning($msg){
        $log = date("Y-m-d h:i:s")." [WARNING] ".$msg."\n";
        $this->log($log);
    }

    public function Error($msg){
        $log = date("Y-m-d h:i:s")." [ERROR] ".$msg."\n";
        $this->log($log);
    }

    public function Create($msg){
        $log = date("Y-m-d h:i:s")." [CREATE] ".$msg."\n";
        $this->log($log);
    }

    public function Load($msg){
        $log = date("Y-m-d h:i:s")." [LOAD] ".$msg."\n";
        $this->log($log);
    }

    public function Info($msg){
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        $this->log($log);
    }

    public function Table($msg){
        $log = date("Y-m-d h:i:s")." [TABLE] ".$msg."\n";
        $this->log($log);
    }

    public function User($msg){
        $log = date("Y-m-d h:i:s")." [USER] ".$msg."\n";
        $this->log($log);
    }

    public function InfoCache($msg){
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        echo($log);
        $this->infoCache = $this->infoCache.$log;
    }

    public function DropCache(){
        if($this->LonaDB->config["logging"]) fwrite($this->LogFile, $this->infoCache);
    }
}