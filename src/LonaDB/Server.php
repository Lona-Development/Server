<?php

namespace LonaDB;

use Exception;
use LonaDB\Enums\ErrorCode;
use pmmp\thread\Thread;
use pmmp\thread\ThreadSafeArray;

class Server extends Thread
{
    private ThreadSafeArray $config;
    private ThreadSafeArray $actions;

    private string $address;
    private int $port;

    private $socket;

    private LonaDB $lonaDB;

    /**
     * Constructor for the Server class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        $this->config = $lonaDB->config;

        $this->address = $this->config["address"];
        $this->port = $this->config["port"];

        $this->actions = new ThreadSafeArray();
    }

    /**
     * Starts the server thread.
     */
    public function run(): void
    {
        error_reporting(E_ERROR | E_PARSE);
        require_once __DIR__."/../vendor/autoload.php";
        $this->loadActions();
        $this->startSocket();
    }

    /**
     * Loads all networking actions from the Actions directory.
     */
    private function loadActions(): void
    {
        $actionFiles = scandir(__DIR__."/Actions/");

        foreach ($actionFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                // Set variable actionName to the file name without extension
                $actionName = pathinfo($file, PATHINFO_FILENAME);
                $this->actions[$actionName] = require(__DIR__."/Actions/".$file);
                $this->lonaDB->getLogger()->info("Loaded Networking action from file '".$actionName."'");
            }
        }
    }

    /**
     * Starts the TCP socket server.
     */
    public function startSocket(): void
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEPORT, 1);

        if (!$this->socket) {
            $this->lonaDB->getLogger()->error("Failed to create socket: ".socket_strerror(socket_last_error()));
            return;
        }

        if (!socket_bind($this->socket, $this->address, $this->port)) {
            $this->lonaDB->getLogger()->error("Failed to bind socket: ".socket_strerror(socket_last_error()));
            return;
        }

        if (!socket_listen($this->socket)) {
            $this->lonaDB->getLogger()->error("Failed to listen on socket: ".socket_strerror(socket_last_error()));
            return;
        }

        $this->lonaDB->getLogger()->info("PluginManager: Starting to load plugins...");
        $this->lonaDB->getPluginManager()->loadPlugins();

        $this->lonaDB->getLogger()->start("Server running on port ".$this->port);
        $this->socketRunning = true;

        while (true) {
            $client = socket_accept($this->socket);
            if (!$client) {
                $this->lonaDB->getLogger()->error("Failed to accept client connection: ".socket_strerror(socket_last_error()));
                continue;
            }

            // Read data from clients
            $data = socket_read($client, 1024);
            if (!$data) {
                $this->lonaDB->getLogger()->error("Failed to read data from client: ".socket_strerror(socket_last_error()));
                continue;
            }
            $this->handleData($data, $client);
        }
    }

    /**
     * Stops the TCP socket server.
     */
    public function stop(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    /**
     * Handles data received from a client.
     *
     * This function will be rewritten entirely. 
     * No need to refactor it now.
     *
     * @param  string  $dataString  The data received from the client.
     * @param  mixed  $client  The client socket.
     */
    private function handleData(string $dataString, mixed $client): void
    {
        try {
            $data = json_decode($dataString, true);

            if (!is_array($data) || !array_key_exists('action', $data)) {
                $this->lonaDB->getLogger()->error("Invalid data received from client: ".$dataString);
                return;
            }

            if (!$data['process']) {
                $response = json_encode(["success" => false, "err" => ErrorCode::BAD_PROCESS_ID, "process" => $data['process']]);
                $this->lonaDB->getLogger()->error("Invalid process ID received from client: ".$dataString);
                socket_write($client, $response);
                return;
            }

            $key = hash('sha256', $data['process'], true);
            $parts = explode(':', $data['login']['password']);
            $iv = hex2bin($parts[0]);
            $ciphertext = hex2bin($parts[1]);
            $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            $login = $this->lonaDB->getUserManager()->CheckPassword($data['login']['name'], $password);

            if (!$login) {
                $response = json_encode(["success" => false, "err" => ErrorCode::LOGIN_ERROR, "process" => $data['process']]);
                $this->lonaDB->getLogger()->error("Invalid login credentials received from client: ".$dataString);
                socket_write($client, $response);
                return;
            }

            if (!$this->actions[$data['action']]) {
                $response = json_encode(["success" => false, "err" => ErrorCode::ACTION_NOT_FOUND]);
                $this->lonaDB->getLogger()->error("Invalid action received from client: ".$dataString);
                socket_write($client, $response);
                return;
            }

            $this->actions[$data['action']]->run($this->lonaDB, $data, $client);
        } catch (Exception $exception) {
            $this->lonaDB->getLogger()->error($exception->getMessage());
        }
    }
}
