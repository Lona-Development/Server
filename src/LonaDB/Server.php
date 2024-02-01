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
    
    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;
        $this->config = $lonaDB->config;

        $this->address = $this->config["address"];
        $this->port = $this->config["port"];

        $this->loadActions();
        $this->startSocket();
    } 

    private function loadActions() {
        $actionFiles = scandir(__DIR__ . "/Actions/");
        foreach ($actionFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $actionName = pathinfo($file, PATHINFO_FILENAME);
                $this->actions[$actionName] = require(__DIR__ . "/Actions/" . $file);
                $this->LonaDB->Logger->Info("Loaded Networking action from file '".$actionName."'" . PHP_EOL);
            }
        }
    }

    public function startSocket() {
        $socket = stream_socket_server("tcp://".$this->address.":".$this->port, $errno, $errstr);

        if (!$socket) {
            $this->LonaDB->Logger->Error($errno." - ".$errstr . PHP_EOL);
            exit();
        }

        $this->LonaDB->Logger->Info("Server listening on port " . $this->port. PHP_EOL);

        stream_set_blocking($socket, 0);

        while ($client = stream_socket_accept($socket, -1)) {
            $pid = pcntl_fork();
    
            if ($pid == -1) {
            } elseif ($pid) {
                fclose($client);
            } else {
                $this->handleClient($client);
                exit();
            }
        }
    }

    private function handleClient($client) {
        $dataString = stream_get_contents($client, 1048576);
        $this->handleData($dataString, $client);
        fclose($client);
    }

    private function handleData($dataString, $client) {
        try {
            $data = json_decode($dataString, true);

            $password = $data['login']['password'];

            $login = $this->LonaDB->UserManager->CheckPassword($data['login']['name'], $password);

            if (!$login) {
                $response = json_encode(["success" => false, "err" => "login_error", "process" => $data['process']]);
                fwrite($client, $response);
                return;
            }

            if (!$data['process']) {
                $response = json_encode(["success" => false, "err" => "bad_process_id", "process" => $data['process']]);
                fwrite($client, $response);
                return;
            }

            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => "action_not_found"]);
                fwrite($client, $response);
                return;
            }

            try {
                $this->actions[$data['action']]->Run($this->LonaDB, $data, $client);
            } catch (Exception $e) {
                $this->LonaDB->Loggin->Error($e->getMessage() . PHP_EOL);
            }

            ob_end_flush();
            ob_flush();
            flush();
        } catch (Exception $e) {
            $this->LonaDB->Loggin->Error($e->getMessage() . PHP_EOL);
        }
    }
}