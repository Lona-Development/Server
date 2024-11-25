<?php

require 'vendor/autoload.php';

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if the user is root (only root is allowed to use eval)
        if ($lonaDB->userManager->getRole($data['login']['name']) !== "Superuser") {
            //Send response
            $this->sendErrorResponse($client, "not_root", $data['process']);
            return false;
        }
        //Generate an eval script to create a class with the desired function
        $functionName = $data['process'];
        $evalFunction = "
            \$functions['$functionName'] = new class {
                public function execute(\$lonaDB) {
                    " . $data['function'] . "
                }
            };
        ";
        try {
            //Run the script
            eval($evalFunction);
            try {
                //Execute the function
                $answer = $functions[$functionName]->execute($lonaDB);
            } catch (Exception $e) {
                //Catch errors
                $answer = $e->getMessage();
            }
        } catch (Exception $e) {
            //Catch errors
            $answer = $e->getMessage();
        }
        //Send response and close socket
        $this->sendSuccessResponse($client, $answer, $data['process']);
        // Remove the function from the $functions array
        unset($functions[$functionName]);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "eval", [ "content" => $data['function'] ]);
        return true;
    }

    private function sendErrorResponse($client, $error, $process): void {
        //Create a response array
        $response = json_encode(["success" => false, "err" => $error, "process" => $process]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }

    private function sendSuccessResponse($client, $response, $process): void {
        //Create a response array
        $response = json_encode(["success" => true, "response" => $response, "process" => $process]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};