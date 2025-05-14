<?php

namespace LonaDB;

require '../vendor/autoload.php';

use Exception;
use Phar;
use LonaDB\Functions\FunctionManager;
use LonaDB\Plugins\PluginManager;
use LonaDB\Tables\TableManager;
use LonaDB\Users\UserManager;
use pmmp\thread\Thread;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class LonaDB extends ThreadSafe
{
    public ThreadSafeArray $config;
    private string $encryptionKey;

    private Logger $logger;
    private Server $server;
    private TableManager $tableManager;
    private UserManager $userManager;
    private FunctionManager $functionManager;
    private PluginManager $pluginManager;

    /**
     * Constructor for the LonaDB main class.
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

            $this->logger->infoCache("LonaDB v5.0.0");
            $this->logger->infoCache("Looking for config.");

            file_put_contents("configuration.lona", file_get_contents("configuration.lona"));
            if (file_get_contents("configuration.lona") === "") {
                $this->setup();
            }
        
            //Decrypt the configuration file
            $decrypted = LonaDB::decrypt(file_get_contents("configuration.lona"), $this->encryptionKey);

            //Check if being run as a Phar
            if (Phar::running(false) !== "") {
                $run = true;
            }

            if ($run) {
                $this->logger->infoCache("Loading config.");
                
                if(!$decrypted) {
                    $this->logger->errorCache("Failed to decrypt the configuration file.");

                    //Ask user for the hostname of the machine the config was created on
                    $oldHostname = str_replace("\n", "", $this->readInput("Hostname of the machine the config was created on"));
                    $hash = hash('sha256', $oldHostname, true);
                    $decrypted = LonaDB::decrypt(file_get_contents("configuration.lona"), $hash);

                    if(!$decrypted) {
                        $this->logger->errorCache("Failed to decrypt the configuration file.");
                        //Ask user for a encryptionKey
                        $key = str_replace("\n", "", $this->readInput("Encryption key (before v6.0.0)"));
                        $decrypted = LonaDB::decrypt(file_get_contents("configuration.lona"), $key);

                        if(!$decrypted) {
                            $this->logger->errorCache("Failed to decrypt the configuration file.");
                            exit();
                        }
                    }else $this->logger->infoCache("Decrypted configuration file with the old hostname.");
                    //Save the config with the new key
                    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                    $save = json_decode($decrypted, true);
                    
                    $encrypted = openssl_encrypt(json_encode($save), 'aes-256-cbc', $this->encryptionKey, 0, $iv);
                    file_put_contents("./configuration.lona", $encrypted.":".base64_encode($iv));
                    $this->logger->infoCache("Saved configuration file with the new hostname.");
                }

                $this->config = ThreadSafeArray::fromArray(json_decode($decrypted, true));
                
                $this->logger->InfoCache("Checking config.");
                if (!$this->config["port"] || !$this->config["address"] || !$this->config["encryptionKey"] || !$this->config["root"]) {
                    $this->setup();
                }

                $this->logger->loadLogger();
                $this->logger->dropCache();

                $this->logger->info("Loading UserManager class...");
                $this->userManager = new UserManager($this);

                $this->logger->info("Loading TableManager class...");
                $this->tableManager = new TableManager($this);

                $this->logger->info("Loading FunctionManager class...");
                $this->functionManager = new FunctionManager($this);

                $this->logger->info("Loading PluginManager class...");
                $this->pluginManager = new PluginManager($this);

                $this->logger->info("Loading Server class...");
                $this->server = new Server($this);
                $this->server->start(Thread::INHERIT_NONE);
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Returns the version of the LonaDB server.
     *
     * @return string The version of the LonaDB server.
     */
    public function getVersion(): string { return "6.0.0"; }

    /**
     * Returns the base path of the LonaDB server.
     *
     * @return string The base path of the LonaDB server.
     */
    public function getBasePath(): string { 
        $path = Phar::running(false);
        if ($path === "") {
            return __DIR__;
        }
        $parts = explode("/", $path);
        array_pop($parts);
        return implode("/", $parts);
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
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $save = array(
            "port" => $databasePort,
            "address" => str_replace("\n", "", $databaseAddress),
            "logging" => $log,
            "encryptionKey" => str_replace("\n", "", $encryptionKey),
            "root" => str_replace("\n", "", $rootPassword)
        );

        //Encrypt config
        $encrypted = openssl_encrypt(json_encode($save), 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        //Save to configuration.lona
        file_put_contents("./configuration.lona", $encrypted.":".base64_encode($iv));
    }

    /**
     * Reads input from the standard input.
     *
     * @param  string  $title  The prompt message to display.
     * @return false|string The input read from the standard input, or false on failure.
     */
    private function readInput(string $title): false|string
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

    /**
     * @return string The encryption key used to decrypt/encrypt files.
     */
    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    /**
     * Encrypts the given data using the given key.
     *
     * @param  string  $data The data to encrypt.
     * @param  string  $key  The encryption key to use.
     * @return String
     */
    public static function encrypt(string $data, string $key): string
    {
        $iv = openssl_random_pseudo_bytes(\openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv).":".base64_encode($iv);
    }

    /**
     * Decrypts the given data using the given key.
     *
     * @param  string  $data The data to decrypt.
     * @param  string  $key  The encryption key to use.
     * @return String
     */
    public static function decrypt(string $data, string $key): string
    {
        $parts = explode(':', $data);
        return openssl_decrypt($parts[0], 'aes-256-cbc', $key, 0, base64_decode($parts[1]));
    }
}

$keyMaterial = gethostname();
$key = hash('sha256', $keyMaterial, true);

new LonaDB($key);
