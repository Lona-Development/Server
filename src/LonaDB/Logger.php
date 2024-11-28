<?php

namespace LonaDB;

require 'vendor/autoload.php';

class Logger
{

    private $logFile;
    private string $infoCache = "";
    private bool $start = false;

    private LonaDB $lonaDB;

    /**
     * Constructor for the Logger class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
    }

    /**
     * Logs a message with an optional color.
     *
     * @param  string  $message  The message to log.
     * @param  string  $color  The color code for the message (optional).
     */
    private function log(string $message, string $color = ""): void
    {
        echo($color.$message."\e[0m");
        if ($this->lonaDB->config["logging"]) {
            fwrite($this->logFile, $message);
        }
    }

    /**
     * Initializes the logger and creates a log file if logging is enabled.
     */
    public function loadLogger(): void
    {
        if ($this->lonaDB->config["logging"]) {
            $this->logFile = fopen('log.txt', 'a');
        }
    }

    /**
     * Logs a startup message if it hasn't been logged already.
     *
     * @param  string  $msg  The startup message.
     */
    public function start(string $msg): void
    {
        //Check if the Startup message has already been sent
        if (!$this->start) {
            //Declare that the Startup message has been sent
            $this->start = true;

            //Send the Startup message
            $log = date("Y-m-d h:i:s")." [Startup] ".$msg."\n";
            $this->log($log);
        }
    }

    /**
     * Logs an info message to the terminal and caches it.
     *
     * @param  string  $msg  The info message.
     */
    public function infoCache(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        echo($log);

        $this->infoCache = $this->infoCache.$log;
    }

    /**
     * Writes the cached info messages to the log file if logging is enabled.
     */
    public function dropCache(): void
    {
        if ($this->lonaDB->config["logging"]) {
            fwrite($this->logFile, $this->infoCache);
        }
    }

    /**
     * Logs a warning message.
     *
     * @param  string  $msg  The warning message.
     */
    public function warning(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [WARNING] ".$msg."\n";
        $this->log($log, "\033[33m");
    }

    /**
     * Logs an error message.
     *
     * @param  string  $msg  The error message.
     */
    public function error(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [ERROR] ".$msg."\n";
        $this->log($log, "\033[31m");
    }

    /**
     * Logs a create message.
     *
     * @param  string  $msg  The create message.
     */
    public function create(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [CREATE] ".$msg."\n";
        $this->log($log, "\033[32m");
    }

    /**
     * Logs a load message.
     *
     * @param  string  $msg  The load message.
     */
    public function load(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [LOAD] ".$msg."\n";
        $this->log($log);
    }

    /**
     * Logs an info message.
     *
     * @param  string  $msg  The info message.
     */
    public function info(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [INFO] ".$msg."\n";
        $this->log($log, "\033[34m");
    }

    /**
     * Logs a table message.
     *
     * @param  string  $msg  The table message.
     */
    public function table(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [TABLE] ".$msg."\n";
        $this->log($log);
    }

    /**
     * Logs a user message.
     *
     * @param  string  $msg  The user message.
     */
    public function user(string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [USER] ".$msg."\n";
        $this->log($log);
    }

    /**
     * Logs a plugin message.
     *
     * @param  string  $name  The name of the plugin.
     * @param  string  $msg  The plugin message.
     */
    public function plugin(string $name, string $msg): void
    {
        $log = date("Y-m-d h:i:s")." [Plugin] ".$name.": ".$msg."\n";
        $this->log($log, "\033[35m");
    }
}