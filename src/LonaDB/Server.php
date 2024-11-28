<?php

namespace LonaDB;

//Load autoload from composer
require 'vendor/autoload.php';

//Load the Main file
use Exception;

class Server {
    //Create all variables
    private array $config;
    private array $actions = [];

    private string $address;
    private int $port;

    private $socket;
    private bool $socketRunning;

    private LonaDB $lonaDB;

    public function __construct(LonaDB $lonaDB) {
        $this->lonaDB = $lonaDB;

        //Check if the Server is already running
        if($this->lonaDB->running) return;

        //Declare important variables
        $this->lonaDB->running = true;
        $this->socketRunning = false;
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
            //If the file extension is "php"
            if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                //Set variable actionName to the file name without extension
                $actionName = pathinfo($file, PATHINFO_FILENAME);
                //Load an action file to the action array
                $this->actions[$actionName] = require(__DIR__ . "/Actions/" . $file);
                $this->lonaDB->logger->info("Loaded Networking action from file '".$actionName."'");
            }
        }
    }

    public function startSocket() : void {
        //Check if the socket is already running
        if($this->socketRunning) return;

        //Initialize the socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //Check if there was an error while initializing the socket
        if ($this->socket == false) {
            $this->lonaDB->logger->error("Failed to create socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Try binding the socket to the desired IP and port
        if (!socket_bind($this->socket, $this->address, $this->port)) {
            $this->lonaDB->logger->error("Failed to bind socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Try to listen for clients
        if (!socket_listen($this->socket)) {
            $this->lonaDB->logger->error("Failed to listen on socket: " . socket_strerror(socket_last_error()));
            return;
        }

        //Tell the PluginManager to load the plugins
        $this->lonaDB->logger->info("PluginManager: Starting to load plugins...");
        $this->lonaDB->loadPlugins();

        $this->lonaDB->logger->start("Server running on port ".$this->port);
        $this->socketRunning = true;

        while (true) {
            //Accept the connections
            $client = socket_accept($this->socket);
            //If you cannot accept connections
            if ($client == false) {
                $this->lonaDB->logger->error("Failed to accept client connection: " . socket_strerror(socket_last_error()));
                continue;
            }

            //Read data form clients
            $data = socket_read($client, 1024);
            //If you cannot read data
            if ($data == false) {
                $this->lonaDB->logger->error("Failed to read data from client: " . socket_strerror(socket_last_error()));
                continue;
            }

            //Handle data received from a client
            $this->handleData($data, $client);
        }

        socket_close($this->socket);
    }

    public function stop() : void {
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
            $login = $this->lonaDB->userManager->CheckPassword($data['login']['name'], $password);

            //Check if login was successful
            if (!$login) {
                $response = json_encode(["success" => false, "err" => "login_error", "process" => $data['process']]);
                socket_write($client, $response);
                return;
            }

            //Check if the requested action exists
            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => "action_not_found"]);
                socket_write($client, $response);
                return;
            }

            try {
                //Run action
                $this->actions[$data['action']]->run($this->lonaDB, $data, $client);
            } catch (Exception $e) {
                $this->lonaDB->logger->error($e->getMessage());
            }
        } catch (Exception $e) {
            $this->lonaDB->logger->error($e->getMessage());
        }
    }
}
