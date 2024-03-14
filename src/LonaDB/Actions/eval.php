<?php

require 'vendor/autoload.php';
use LonaDB\LonaDB;

return new class {
    public function run($LonaDB, $data, $client) : void {
        if ($data['login']['name'] !== 'root') {
            $this->sendErrorResponse($client, "not_root", $data['process']);
            return;
        }

        $functionName = $data['process'];
        $evalFunction = "
            \$functions['$functionName'] = new class {
                public function Execute(\$LonaDB) {
                    " . $data['function'] . "
                }
            };
        ";

        try {
            eval($evalFunction);

            try {
                $answer = $functions[$functionName]->Execute($LonaDB);
            } catch (Exception $e) {
                $answer = $e->getMessage();
            }
        } catch (Exception $e) {
            $answer = $e->getMessage();
        }

        $this->sendSuccessResponse($client, $answer, $data['process']);
        socket_close($client);

        // Remove the function from the $functions array
        unset($functions[$functionName]);
    }

    private function sendErrorResponse($client, $error, $process): void {
        $response = json_encode(["success" => false, "err" => $error, "process" => $process]);
        socket_write($client, $response);
        socket_close($client);
    }

    private function sendSuccessResponse($client, $response, $process): void {
        $response = json_encode(["success" => true, "response" => $response, "process" => $process]);
        socket_write($client, $response);
    }
};