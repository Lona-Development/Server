<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Role;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the evaluation of code in LonaDB.
 */
return new class extends Action {
 
    /**
     * Runs the action to evaluate code in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to evaluate the code, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the code is evaluated successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if the user is root (only root is allowed to use eval)
        if ($lonaDB->getUserManager()->getRole($data['login']['name'])->isNot(Role::SUPERUSER)) {
            $this->sendErrorResponse($client, ErrorCode::NOT_ROOT, $data['process']);
            return false;
        }
        // Generate an eval script to create a class with the desired function
        $functionName = $data['process'];
        $evalFunction = "
            \$functions['$functionName'] = new class {
                public function execute(\$lonaDB) {
                    " . $data['function'] . "
                }
            };
        ";
        try {
            eval($evalFunction);
            try {
                $answer = $functions[$functionName]->execute($lonaDB);
            } catch (Exception $e) {
                $answer = $e->getMessage();
            }
        } catch (Exception $e) {
            $answer = $e->getMessage();
        }
        // Send response and close socket
        $this->sendSuccessResponse($client, $answer, $data['process']);
        // Remove the function from the $functions array
        unset($functions[$functionName]);
        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::EVAL, [ "content" => $data['function'] ]);
        return true;
    }

    /**
     * Sends an error response to the client.
     *
     * @param  mixed  $client  The client to send the response to.
     * @param  ErrorCode  $error  The error message.
     * @param  string  $process  The process identifier.
     * @return void
     */
    private function sendErrorResponse(mixed $client, ErrorCode $error, string $process): void {
        $response = json_encode(["success" => false, "err" => $error->value, "process" => $process]);
        socket_write($client, $response);
        socket_close($client);
    }

    /**
     * Sends a success response to the client.
     *
     * @param  mixed  $client  The client to send the response to.
     * @param  string  $response  The response message.
     * @param  string  $process  The process identifier.
     * @return void
     */
    private function sendSuccessResponse(mixed $client, string $response, string $process): void {
        $response = json_encode(["success" => true, "response" => $response, "process" => $process]);
        socket_write($client, $response);
        socket_close($client);
    }
};
