<?php

namespace LonaDB;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

//Load autoload from composer
require 'vendor/autoload.php';

//Load Server, Logger and Managers
use LonaDB\Server;
use LonaDB\Logger;
use LonaDB\Tables\TableManager;
use LonaDB\Users\UserManager;
use LonaDB\Functions\FunctionManager;
use LonaDB\Plugins\PluginManager;

class LonaDB {
    //Create all variables
    public array $config;
    public bool $Running = false;
    public string $EncryptionKey;

    public Logger $Logger;
    public Server $Server;
    public TableManager $TableManager;
    public UserManager $UserManager;
    public FunctionManager $FunctionManager;
    public PluginManager $PluginManager;

    public function __construct(string $key) {
        //EncryptionKey is used to decrypt the configuration.lona file 
        $this->EncryptionKey = $key;
        //Create an instance of the Logger
        $this->Logger = new Logger($this);
        //Run variable, used to know if the configuration was decrypted successfully
        $run = false;
        
        try{
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            error_reporting(E_ERROR | E_PARSE);
            
            $this->Logger->InfoCache("LonaDB v4.6.0");
            $this->Logger->InfoCache("Looking for config.");

            //Create an empty configuration.lona if it doesn't exist. file_exists gave an error because we run LonaDB as a phar. 
            file_put_contents("configuration.lona", file_get_contents("configuration.lona"));
            //If the file didn't exist before, run setup
            if(file_get_contents("configuration.lona") === "") $this->setup();
            //If the file did exist before, decrypt it
            else{
                //Split the encrypted content from the IV
                $parts = explode(':', file_get_contents("./configuration.lona"));
                //Decrypt the config using the EncryptionKey and IV
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->EncryptionKey, 0, base64_decode($parts[1]));

                //If the given EncryptionKey didn't work, throw an error and exit
                if(!json_decode($decrypted, true)) {
                    echo "Encryption Key does not match the existing Configuration file. Exiting.\n";
                }
                //Else, start the DB server
                else $run = true;
            }

            //If the configuration was decrypted successfully
            if($run){
                $this->Logger->InfoCache("Loading config.");
                //Split encrypted from IV
                $parts = explode(':', file_get_contents("./configuration.lona"));
                //Decrypt using EncryptionKey and IV
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->EncryptionKey, 0, base64_decode($parts[1]));
                //Load the config
                $this->config = json_decode($decrypted, true);

                $this->Logger->InfoCache("Checking config.");
                //Check if the configuration is missing something
                if(!$this->config["port"] || !$this->config["address"] || !$this->config["encryptionKey"] || !$this->config["root"]) {
                    //Configuration is missing a variable => Setup
                    $this->setup();
                }

                //Check if logging to a file is enabled, if it is, write logs to the file
                $this->Logger->LoadLogger();
                //Dop the chached logs to the file (if enabled)
                $this->Logger->DropCache();

                //Initialize Manaers
                $this->Logger->Info("Loading TableManager class.");
                $this->TableManager = new TableManager($this);
                $this->Logger->Info("Loading UserManager class.");
                $this->UserManager = new UserManager($this);
                $this->Logger->Info("Loading FunctionManager class.");
                $this->FunctionManager = new FunctionManager($this);
                $this->Logger->Info("Loading PluginManager class.");
                $this->PluginManager = new PluginManager($this);

                //If the server is already runnning
                //This is needed because in some ocasions we had the server start twice, stopping the first one and throwing an error
                if(!$this->Running){
                    //Initialize server
                    $this->Logger->Info("Loading Server class.");
                    $this->Server = new Server($this);
                }
            }
        }
        catch (\Exception $e){
            $this->Logger->Error($e);
        }
    }

    //Tell the PluginManager to load all plugins
    public function LoadPlugins() : void {
        //Check if the Plugins have already been loaded
        if($this->PluginManager->Loaded) return;

        //Load Plugins
        $this->PluginManager->LoadPlugins();
    }

    //Setup script
    private function setup() : void {
        $this->Logger->InfoCache("Invalid or missing config. Starting setup.");

        //Port
        echo "Database port:\n";
        $portHandle = fopen ("php://stdin","r");
        $port = intval(str_replace("\n", "", fgets($portHandle)));
        fclose($portHandle);

        //IP
        echo "Database address:\n";
        $addrHandle = fopen ("php://stdin","r");
        $addr = fgets($addrHandle);
        fclose($addrHandle);

        //EncryptionKey for all data
        echo "Data encryption key:\n";
        $keyHandle = fopen ("php://stdin","r");
        $key = fgets($keyHandle);
        fclose($keyHandle);

        //Root password
        echo "Password for root user:\n";
        $rootHandle = fopen ("php://stdin","r");
        $root = fgets($rootHandle);
        fclose($rootHandle);

        //Logging to file
        echo "Enable logging to a file? (y/N):\n";
        $logHandle = fopen ("php://stdin","r");
        $logAns = fgets($logHandle);
        fclose($logHandle);
        $log = false;
        if(trim(strtolower($logAns)) === "y") $log = true;

        $this->Logger->InfoCache("Saving config.");

        //Create IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //Put input into an array
        $save = array(
            "port" => $port, 
            "address" => str_replace("\n","",$addr), 
            "logging" => $log, 
            "encryptionKey" => str_replace("\n","",$key),
            "root" => str_replace("\n","",$root)
        );

        //Encrypt config
        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->EncryptionKey, 0, $iv);
        //Save to configuration.lona
        file_put_contents("./configuration.lona", $encrypted.":".base64_encode($iv));
    }
    
    //Stop script
    public function Stop() : void {
        echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
        echo "[Shutdown]\n";
        echo "Killing threads...\n";
        //Stop the server
        $this->Server->Stop();
        //Kill plugin Threads
        $this->PluginManager->KillThreads();
        echo "Done!\n";
        exit();
    }
}

//Ask for the EncryptionKey
echo "Encryption key:\n";
$keyHandle = fopen ("php://stdin","r");
$key = fgets($keyHandle);
fclose($keyHandle);

$encryptionKey = str_replace("\n","",$key);
unset($key);

//Initialize LonaDB
$run = new LonaDB($encryptionKey);