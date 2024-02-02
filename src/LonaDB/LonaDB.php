<?php

namespace LonaDB;

define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';
use LonaDB\Server;
use LonaDB\Logger;
use LonaDB\Tables\TableManager;
use LonaDB\Users\UserManager;

class LonaDB {
    public array $config;
    public string $EncryptionKey;

    public Logger $Logger;
    public Server $Server;
    public TableManager $TableManager;
    public UserManager $UserManager;

    public function __construct() {
        echo "Encryption key:\n";
        $keyHandle = fopen ("php://stdin","r");
        $key = fgets($keyHandle);
        fclose($keyHandle);

        $this->EncryptionKey = str_replace("\n","",$key);
        unset($key);

        $this->Logger = new Logger($this);
        
        try{
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            error_reporting(E_ERROR | E_PARSE);

            $this->Logger->InfoCache("Looking for config.");

            //somehow file_exists() always retuns false for me... But this checks if the file did exist in the first place.
            file_put_contents("configuration.lona", file_get_contents("configuration.lona"));
            if(file_get_contents("configuration.lona") === "") $this->setup();

            else{
                $parts = explode(':', file_get_contents("./configuration.lona"));
                $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->EncryptionKey, 0, base64_decode($parts[1]));
                if(!json_decode($decrypted, true)) {
                    echo "Encryption Key does not match the existing Configuration file. Exiting.\n";
                    exit;
                }
            }

            $this->Logger->InfoCache("Loading config.");
            $parts = explode(':', file_get_contents("./configuration.lona"));
            $decrypted = openssl_decrypt($parts[0], AES_256_CBC, $this->EncryptionKey, 0, base64_decode($parts[1]));
            $this->config = json_decode($decrypted, true);

            $this->Logger->InfoCache("Checking config.");
            if(!$this->config["port"] || !$this->config["address"] || !$this->config["encryptionKey"] || !$this->config["root"]) {
                $this->setup();
            }

            $this->Logger->LoadLogger();
            $this->Logger->DropCache();

            $this->Logger->Info("Loading TableManager class.");
            $this->TableManager = new TableManager($this);
            $this->Logger->Info("Loading UserManager class.");
            $this->UserManager = new UserManager($this);
            $this->Logger->Info("Loading Server class.");
            $this->Server = new Server($this);        
        }
        catch (\Exception $e){
            $this->Logger->Error($e);
        }
    }

    private function setup() : void {
        $this->Logger->InfoCache("Invalid or missing config. Starting setup.");
        echo "Database port:\n";
        $portHandle = fopen ("php://stdin","r");
        $port = intval(str_replace("\n", "", fgets($portHandle)));
        fclose($portHandle);

        echo "Database address:\n";
        $addrHandle = fopen ("php://stdin","r");
        $addr = fgets($addrHandle);
        fclose($addrHandle);

        echo "Table encryption key:\n";
        $keyHandle = fopen ("php://stdin","r");
        $key = fgets($keyHandle);
        fclose($keyHandle);

        echo "Password for root user:\n";
        $rootHandle = fopen ("php://stdin","r");
        $root = fgets($rootHandle);
        fclose($rootHandle);

        echo "Enable logging? (y/N):\n";
        $logHandle = fopen ("php://stdin","r");
        $logAns = fgets($logHandle);
        fclose($logHandle);
        $log = false;
        if(trim(strtolower($logAns)) === "y") $log = true;

        $this->Logger->InfoCache("Saving config.");

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = array(
            "port" => $port, 
            "address" => str_replace("\n","",$addr), 
            "logging" => $log, 
            "encryptionKey" => str_replace("\n","",$key),
            "root" => str_replace("\n","",$root)
        );

        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->EncryptionKey, 0, $iv);
        file_put_contents("./configuration.lona", $encrypted.":".base64_encode($iv));
    }
}

$run = new LonaDB();
