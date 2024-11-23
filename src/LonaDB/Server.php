<?php

namespace LonaDB;

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file
use LonaDB\LonaDB;

class Server {
    //Create all variables
    private array $config;
    private array $actions = [];

    private string $address;
    private int $port;

    private $socket;
    private bool $SocketRunning;

    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;

        //Check if the Server is already running
        if($this->LonaDB->Running) return;

        //Declare important variables
        $this->LonaDB->Running = true;
        $this->SocketRunning = false;
        $this->config = $lonaDB->config;

        //IP and port for the TCP Socket
        $this->address = $this->config["address"];
        $this->port = $this->config["port"];

        //Load all networking actions
        $this->loadActions();
        //Start TCP Socket
        $this->startSocket();
    }

    private function loadActions() : void {
        //Scan the Actions directory IN THE PHAR FILE
        $actionFiles = scandir(__DIR__ . "/Actions/");

        //Loop through all action files
        foreach ($actionFiles as $file) {
            //If file extension is "php"
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                //Set variable actionName to the file name without extension
                $actionName = pathinfo($file, PATHINFO_FILENAME);
                //Load action file to the actions array
                $this->actions[$actionName] = require(__DIR__ . "/Actions/" . $file);
                $this->LonaDB->Logger->Info("Loaded Networking action from file '".$actionName."'");
            }
        }
    }

    public function startSocket() : void {
        //Check if the socket is already running
        if($this->LonaDB->SocketRunning) return;

        //Initialize the socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //Check if there was an error while initializing the socket
        if ($this->socket === false) {
            $this->LonaDB->Logger->Error("Failed to create socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Try binding the socket to the desired IP and port
        if (!socket_bind($this->socket, $this->address, $this->port)) {
            $this->LonaDB->Logger->Error("Failed to bind socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Try to listen for clients
        if (!socket_listen($this->socket)) {
            $this->LonaDB->Logger->Error("Failed to listen on socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Tell the PluginManager to load the plugins
        $this->LonaDB->Logger->Info("PluginManager: Starting to load plugins...");
        $this->LonaDB->LoadPlugins();

        $this->LonaDB->Logger->Start("Server running on port ".$this->port);
        $this->SocketRunning = true;

        while (true) {
            //Accept the connections
            $client = socket_accept($this->socket);
            //If cannot accept connections
            if ($client === false) {
                $this->LonaDB->Logger->Error("Failed to accept client connection: " . socket_strerror(socket_last_error()));
                continue;
            }

            //Read data form clients
            $data = socket_read($client, 1024);
            //If cannot read data
            if ($data === false) {
                $this->LonaDB->Logger->Error("Failed to read data from client: " . socket_strerror(socket_last_error()));
                continue;
            }

            //Handle data recieved from client
            $this->handleData($data, $client);
        }

        socket_close($this->socket);
    }

    public function Stop() : void {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    private function handleData(string $dataString, $client) : void {
        try {
            //Convert data from JSON to PHP Array
            $data = json_decode($dataString, true);

            //Check if the data has been converted successfully
            if(!is_array($data)) return;

            //Check if action has been set
            if(!array_key_exists('action', $data)) return;

            //Check if data contains a ProcessID
            if (!$data['process']) {
                $response = json_encode(["success" => false, "err" => "bad_process_id", "process" => $data['process']]);
                socket_write($client, $response);
                return;
            }
            
            //Create a hash from the ProcessID to get the encryption key for the password
            $key = hash('sha256', $data['process'], true);
            //Split the encrypted password from the IV
            $parts = explode(':', $data['login']['password']);
            //Get IV and ciphertext
            $iv = hex2bin($parts[0]);
            $ciphertext = hex2bin($parts[1]);
            //Decrypt the password
            $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            //Try logging in with the username and decrypted password
            $login = $this->LonaDB->UserManager->CheckPassword($data['login']['name'], $password);

            //Check if login was successfull
            if (!$login) {
                $response = json_encode(["success" => false, "err" => "login_error", "process" => $data['process']]);
                socket_write($client, $response);
                return;
            }

            //Check if requested action exists
            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => "action_not_found"]);
                socket_write($client, $response);
                return;
            }

            try {
                //Run action
                $this->actions[$data['action']]->Run($this->LonaDB, $data, $client);
            } catch (Exception $e) {
                $this->LonaDB->Loggin->Error($e->getMessage());
            }
        } catch (Exception $e) {
            $this->LonaDB->Loggin->Error($e->getMessage());
        }
    }
}
