<?php

require 'vendor/autoload.php';
use LonaDB\LonaDB;

//TODO: Refactoring the eval action


return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user is root (only root is allowed to use eval)
        if ($LonaDB->UserManager->GetRole($data['login']['name']) !== "Superuser") {
            //Send response
            $this->sendErrorResponse($client, "not_root", $data['process']);
            return;
        }
        //Generate eval script to create a class with the desired function
        $functionName = $data['process'];
        $evalFunction = "
            \$functions['$functionName'] = new class {
                public function Execute(\$LonaDB) {
                    " . $data['function'] . "
                }
            };
        ";
        try {
            //Run the script
            eval($evalFunction);
            try {
                //Execute the function
                $answer = $functions[$functionName]->Execute($LonaDB);
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
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "eval", [ "content" => $data['function'] ]);
    }

    private function sendErrorResponse($client, $error, $process): void {
        //Create response array
        $response = json_encode(["success" => false, "err" => $error, "process" => $process]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }

    private function sendSuccessResponse($client, $response, $process): void {
        //Create response array
        $response = json_encode(["success" => true, "response" => $response, "process" => $process]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};