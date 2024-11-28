<?php

namespace LonaDB;

//Encryption/decryption
define('AES_256_CBC', 'aes-256-cbc');

require '../vendor/autoload.php';

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

    private Logger $logger;
    public Server $server;
    private TableManager $tableManager;
    private UserManager $userManager;
    private FunctionManager $functionManager;
    private PluginManager $pluginManager;

    /**
     * Constructor for the LonaDB class.
     *
     * @param  string  $key  The encryption key used to decrypt the configuration file.
     */
    public function __construct(string $key)
    {
        $this->encryptionKey = $key;
        $this->logger = new Logger($this);
        $run = false;

        try {
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            error_reporting(E_ERROR | E_PARSE);

            $this->logger->infoCache("LonaDB v4.6.0");
            $this->logger->infoCache("Looking for config.");

            file_put_contents("configuration.lona", file_get_contents("configuration.lona"));
            if (file_get_contents("configuration.lona") === "") {
                $this->setup();
            } else {
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

            if ($run) {
                $this->logger->infoCache("Loading config.");
                $parts = explode(':', file_get_contents("./configuration.lona"));
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->encryptionKey, 0, base64_decode($parts[1]));
                $this->config = json_decode($decrypted, true);

                $this->logger->InfoCache("Checking config.");
                if (!$this->config["port"] || !$this->config["address"] || !$this->config["encryptionKey"] || !$this->config["root"]) {
                    $this->setup();
                }

                $this->logger->loadLogger();
                $this->logger->dropCache();

                $this->logger->info("Loading TableManager class...");
                $this->tableManager = new TableManager($this);
                $this->logger->info("Loading UserManager class...");
                $this->userManager = new UserManager($this);
                $this->logger->info("Loading FunctionManager class...");
                $this->functionManager = new FunctionManager($this);
                $this->logger->info("Loading PluginManager class...");
                $this->pluginManager = new PluginManager($this);

                if (!$this->running) {
                    $this->logger->info("Loading Server class...");
                    $this->server = new Server($this);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Tells the PluginManager to load all plugins.
     */
    public function loadPlugins(): void
    {
        if (!$this->pluginManager->loaded) {
            $this->pluginManager->loadPlugins();
        }
    }

    /**
     * Setup script to initialize the configuration.
     */
    private function setup(): void
    {
        $this->logger->infoCache("Invalid or missing config. Starting setup.");

        $databasePort = intval(str_replace("\n", "", $this->readInput("Database port")));
        $databaseAddress = $this->readInput("Database address");
        $encryptionKey = $this->readInput("Data encryption key");
        $rootPassword = $this->readInput("Password for root user");

        //Logging to file
        echo "Enable logging to a file? (y/N):\n";
        $logHandle = fopen("php://stdin", "r");
        $logAns = fgets($logHandle);
        fclose($logHandle);
        $log = false;
        if (trim(strtolower($logAns)) === "y") {
            $log = true;
        }

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
     * @param  string  $title  The prompt message to display.
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
        $this->server->stop();
        $this->pluginManager->killThreads();
        echo "Done!\n";
        exit();
    }

    /**
     * @return FunctionManager
     */
    public function getFunctionManager(): FunctionManager
    {
        return $this->functionManager;
    }


    /**
     * @return PluginManager
     */
    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * @return TableManager
     */
    public function getTableManager(): TableManager
    {
        return $this->tableManager;
    }

    /**
     * @return UserManager
     */
    public function getUserManager(): UserManager
    {
        return $this->userManager;
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
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