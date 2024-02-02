<?php

namespace LonaDB;

require 'vendor/autoload.php';
use LonaDB\LonaDB;
use OpenSwoole\Server as TCPServer;

class Server {
    private array $config;
    private LonaDB $LonaDB;

    private TCPServer $server;
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
        $this->server = new TCPServer($this->address, $this->port);

        $this->server->on('start', function ($server)
        {
            $this->LonaDB->Logger->Info("Server running on port ".$this->port);
        });
        
        $this->server->on('receive', function (TCPServer $server, int $fd, int $fromId, string $data) {
            $this->handleData($data, $server, $fd);
        });

        $this->server->start();
    }

    private function handleData(string $dataString, TCPServer $server, int $fd) : void {
        try {
            $data = json_decode($dataString, true);

            $password = $data['login']['password'];

            $login = $this->LonaDB->UserManager->CheckPassword($data['login']['name'], $password);

            if (!$login) {
                $response = json_encode(["success" => false, "err" => "login_error", "process" => $data['process']]);
                $server->send($fd, $response);
                $server->close($fd);
                return;
            }

            if (!$data['process']) {
                $response = json_encode(["success" => false, "err" => "bad_process_id", "process" => $data['process']]);
                $server->send($fd, $response);
                $server->close($fd);
                return;
            }

            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => "action_not_found"]);
                $server->send($fd, $response);
                $server->close($fd);
                return;
            }

            try {
                $this->actions[$data['action']]->Run($this->LonaDB, $data, $server, $fd);
            } catch (Exception $e) {
                $this->LonaDB->Loggin->Error($e->getMessage());
            }
        } catch (Exception $e) {
            $this->LonaDB->Loggin->Error($e->getMessage());
        }
    }
}