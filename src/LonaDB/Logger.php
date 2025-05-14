<?php

namespace LonaDB;

use pmmp\thread\ThreadSafe;

class Logger extends ThreadSafe
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
        $finalMessage = date("Y-m-d h:i:s").$message."\n";
        echo($color.$finalMessage."\e[0m");
        if ($this->lonaDB->config["logging"]) {
            fwrite($this->logFile, $finalMessage);
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
     * @param  string  $message  The startup message.
     */
    public function start(string $message): void
    {
        if (!$this->start) {
            $this->start = true;
            $this->log("[Startup] ".$message);
        }
    }

    /**
     * Logs an info message to the terminal and caches it.
     *
     * @param  string  $message  The info message.
     */
    public function infoCache(string $message): void
    {
        $log = "[INFO] ".$message."\n";
        echo("\033[34m".$log);
        $this->infoCache = $this->infoCache.date("Y-m-d h:i:s").' '.$log;
    }

    /**
     * Logs an error message to the terminal and caches it.
     *
     * @param  string  $message  The error message.
     */
    public function errorCache(string $message): void
    {
        $log = "[ERROR] ".$message."\n";
        echo("\033[31m".$log."\e[0m");
        $this->infoCache = $this->infoCache.date("Y-m-d h:i:s").' '.$log;
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
     * @param  string  $message  The warning message.
     */
    public function warning(string $message): void
    {
        $this->log("[WARNING] ".$message, "\033[33m");
    }

    /**
     * Logs an error message.
     *
     * @param  string  $message  The error message.
     */
    public function error(string $message): void
    {
        $this->log("[ERROR] ".$message, "\033[31m");
    }

    /**
     * Logs a create message.
     *
     * @param  string  $message  The create message.
     */
    public function create(string $message): void
    {
        $this->log("[CREATE] ".$message, "\033[32m");
    }

    /**
     * Logs a load message.
     *
     * @param  string  $message  The load message.
     */
    public function load(string $message): void
    {
        $this->log("[LOAD] ".$message);
    }

    /**
     * Logs an info message.
     *
     * @param  string  $message  The info message.
     */
    public function info(string $message): void
    {
        $this->log("[INFO] ".$message, "\033[34m");
    }

    /**
     * Logs a table message.
     *
     * @param  string  $message  The table message.
     */
    public function table(string $message): void
    {
        $this->log("[TABLE] ".$message);
    }

    /**
     * Logs a user message.
     *
     * @param  string  $message  The user message.
     */
    public function user(string $message): void
    {
        $this->log("[USER] ".$message);
    }

    /**
     * Logs a plugin message.
     *
     * @param  string  $name  The name of the plugin.
     * @param  string  $message  The plugin message.
     */
    public function plugin(string $name, string $message): void
    {
        $this->log("[Plugin] ".$name.": ".$message, "\033[35m");
    }
}
