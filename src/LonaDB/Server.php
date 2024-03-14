<?php

namespace LonaDB;

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class Server {
    private array $config;
    private LonaDB $LonaDB;

    private string $address;
    private int $port;

    private array $actions = [];
    private $socket;
    private bool $SocketRunning;

    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;

        if($this->LonaDB->Running) return;

        $this->LonaDB->Running = true;
        $this->SocketRunning = false;
        $this->config = $lonaDB->config;

        $this->address = $this->config["address"];
        $this->port = $this->config["port"];

        $this->loadActions();
        $this->startSocket();
    }

    private function loadActions() : void {
        $actionFiles = scandir(__DIR__ . "/Actions/");
        foreach ($actionFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $actionName = pathinfo($file, PATHINFO_FILENAME);
                $this->actions[$actionName] = require(__DIR__ . "/Actions/" . $file);
                $this->LonaDB->Logger->Info("Loaded Networking action from file '".$actionName."'");
            }
        }
    }

    public function startSocket() : void {
        if($this->LonaDB->SocketRunning) return;
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        if ($this->socket === false) {
            $this->LonaDB->Logger->Error("Failed to create socket: " . socket_strerror(socket_last_error()));
            return;
        }

        if (!socket_bind($this->socket, $this->address, $this->port)) {
            $this->LonaDB->Logger->Error("Failed to bind socket: " . socket_strerror(socket_last_error()));
            return;
        }

        if (!socket_listen($this->socket)) {
            $this->LonaDB->Logger->Error("Failed to listen on socket: " . socket_strerror(socket_last_error()));
            return;
        }

        $this->LonaDB->LoadPlugins();

        if($this->SocketRunning === false){
            $this->LonaDB->Logger->Start("Server running on port ".$this->port);
            $this->SocketRunning = true;
        }

        while (true) {
            $client = socket_accept($this->socket);
            if ($client === false) {
                $this->LonaDB->Logger->Error("Failed to accept client connection: " . socket_strerror(socket_last_error()));
                continue;
            }

            $data = socket_read($client, 1024);
            if ($data === false) {
                $this->LonaDB->Logger->Error("Failed to read data from client: " . socket_strerror(socket_last_error()));
                continue;
            }

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
            $data = json_decode($dataString, true);
            
            $key = hash('sha256', $data['process'], true);
            $parts = explode(':', $data['login']['password']);
            $iv = hex2bin($parts[0]);
            $ciphertext = hex2bin($parts[1]);
            $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            $login = $this->LonaDB->UserManager->CheckPassword($data['login']['name'], $password);

            if (!$login) {
                $response = json_encode(["success" => false, "err" => "login_error", "process" => $data['process']]);
                socket_write($client, $response);
                return;
            }

            if (!$data['process']) {
                $response = json_encode(["success" => false, "err" => "bad_process_id", "process" => $data['process']]);
                socket_write($client, $response);
                return;
            }

            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => "action_not_found"]);
                socket_write($client, $response);
                return;
            }

            try {
                $this->actions[$data['action']]->Run($this->LonaDB, $data, $client);
            } catch (Exception $e) {
                $this->LonaDB->Loggin->Error($e->getMessage());
            }
        } catch (Exception $e) {
            $this->LonaDB->Loggin->Error($e->getMessage());
        }
    }
}
