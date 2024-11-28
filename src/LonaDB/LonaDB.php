<?php

namespace LonaDB;

//Encryption/decryption
define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';

use Exception;
use JetBrains\PhpStorm\NoReturn;
use LonaDB\Functions\FunctionManager;
use LonaDB\Plugins\PluginManager;
use LonaDB\Tables\TableManager;
use LonaDB\Users\UserManager;

class LonaDB
{

    public array $config;
    public bool $running = false;
    public string $encryptionKey;

    public Logger $logger;
    public Server $server;
    public TableManager $tableManager;
    public UserManager $userManager;
    public FunctionManager $functionManager;
    public PluginManager $pluginManager;

    /**
     * Constructor for the LonaDB class.
     *
     * @param  string  $key  The encryption key used to decrypt the configuration file.
     */
    public function __construct(string $key)
    {
        //EncryptionKey is used to decrypt the configuration.lona file
        $this->encryptionKey = $key;
        $this->logger = new Logger($this);
        //Run variable, used to know if the configuration was decrypted successfully
        $run = false;

        try {
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            error_reporting(E_ERROR | E_PARSE);

            $this->logger->infoCache("LonaDB v4.6.0");
            $this->logger->infoCache("Looking for config.");

            //Create an empty configuration.lona if it doesn't exist.
            //File_exists gave an error because we run LonaDB as a phar.
            file_put_contents("configuration.lona", file_get_contents("configuration.lona"));
            //If the file didn't exist before, run setup

            if(file_get_contents("configuration.lona") == "") $this->setup();
            //If the file did exist before, decrypt it
            else{
                //Split the encrypted content from the IV
                $parts = explode(':', file_get_contents("./configuration.lona"));
                //Decrypt the config using the EncryptionKey and IV
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->encryptionKey, 0, base64_decode($parts[1]));

                //If the given EncryptionKey didn't work, throw an error and exit
                if (!json_decode($decrypted, true)) {
                    echo "Encryption Key does not match the existing Configuration file. Exiting.\n";
                } else {
                    $run = true;
                }
            }

            //If the configuration was decrypted successfully
            if ($run) {
                $this->logger->infoCache("Loading config.");
                //Split encrypted from IV
                $parts = explode(':', file_get_contents("./configuration.lona"));
                //Decrypt using EncryptionKey and IV
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->encryptionKey, 0, base64_decode($parts[1]));
                $this->config = json_decode($decrypted, true);

                $this->logger->InfoCache("Checking config.");
                //Check if the configuration is missing something
                if (!$this->config["port"] || !$this->config["address"] || !$this->config["encryptionKey"] || !$this->config["root"]) {
                    $this->setup();
                }

                $this->logger->loadLogger();
                $this->logger->dropCache();

                $this->logger->info("Loading TableManager class.");
                $this->tableManager = new TableManager($this);
                $this->logger->info("Loading UserManager class.");
                $this->userManager = new UserManager($this);
                $this->logger->info("Loading FunctionManager class.");
                $this->functionManager = new FunctionManager($this);
                $this->logger->info("Loading PluginManager class.");
                $this->pluginManager = new PluginManager($this);

                //If the server is already running,
                //This is needed because in some ocasions we had the server start twice, stopping the first one and throwing an error
                if (!$this->running) {
                    $this->logger->Info("Loading Server class.");
                    $this->server = new Server($this);
                }
            }
        } catch (Exception $e) {
            $this->logger->Error($e);
        }
    }

    /**
     * Tells the PluginManager to load all plugins.
     */
    public function loadPlugins(): void
    {
        if ($this->pluginManager->loaded) {
            return;
        }
        $this->pluginManager->loadPlugins();
    }

    /**
     * Setup script to initialize the configuration.
     */
    private function setup(): void
    {
        $this->logger->infoCache("Invalid or missing config. Starting setup.");

        $databasePort = intval(str_replace("\n", "", $this->readInput("Database port:")));
        $databaseAddress = $this->readInput("Database address:");
        $encryptionKey = $this->readInput("Data encryption key:");
        $rootPassword = $this->readInput("Password for root user:");

        //Logging to file
        echo "Enable logging to a file? (y/N):\n";
        $logHandle = fopen("php://stdin", "r");
        $logAns = fgets($logHandle);
        fclose($logHandle);
        $log = false;
      
        if(trim(strtolower($logAns)) == "y") $log = true;

        $this->logger->infoCache("Saving config.");

        //Create IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = array(
            "port" => $databasePort,
            "address" => str_replace("\n", "", $databaseAddress),
            "logging" => $log,
            "encryptionKey" => str_replace("\n", "", $encryptionKey),
            "root" => str_replace("\n", "", $rootPassword)
        );

        //Encrypt config
        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->encryptionKey, 0, $iv);
        //Save to configuration.lona
        file_put_contents("./configuration.lona", $encrypted.":".base64_encode($iv));
    }

    /**
     * Reads input from the standard input.
     *
     * @param string $title The prompt message to display.
     * @return false|string The input read from the standard input, or false on failure.
     */
    public function readInput(string $title): false|string
    {
        echo "$title:\n";
        $inputHandle = fopen("php://stdin", "r");
        $input = fgets($inputHandle);
        fclose($inputHandle);
        return $input;
    }

    /**
     * Stops the LonaDB server and kills plugin threads.
     */
    #[NoReturn]
    public function stop(): void
    {
        echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
        echo "[Shutdown]\n";
        echo "Killing threads...\n";
        //Stop the server
        $this->server->stop();
        //Kill plugin Threads
        $this->pluginManager->killThreads();
        echo "Done!\n";
        exit();
    }
}

//Ask for the EncryptionKey
echo "Encryption key:\n";
$keyHandle = fopen("php://stdin", "r");
$key = fgets($keyHandle);
fclose($keyHandle);

$encryptionKey = str_replace("\n", "", $key);
unset($key);

new LonaDB($encryptionKey);