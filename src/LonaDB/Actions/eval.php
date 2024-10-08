<?php

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
        $success = true;
        try {
            //Run the script
            eval($evalFunction);
            try {
                //Execute the function
                //$result will be the returned variable from the eval script
                $result = $functions[$functionName]->Execute($LonaDB);
            } catch (Exception $e) {
                //Catch errors
                $result = $e->getMessage();
                $success = false;
            }
        } catch (Exception $e) {
            //Catch errors
            $result = $e->getMessage();
            $success = false;
        }
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "eval", [ "content" => $data['function'] ]);
        // Remove the function from the $functions array
        unset($functions[$functionName]);
        //Send response
        return $this->Send($client, ["success" => $success, "result" => $result, "process" => $process]);;
    }

    private function Send ($client, $responseArray) : bool {
        //Convert response array to JSON object
        $response = json_encode($responseArray);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Return state
        $bool = false;
        if($responseArray['success']) $bool = true;
        return $bool;
    }
};