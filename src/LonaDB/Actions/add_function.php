<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if the user is allowed to create functions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "create_function"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Create function
        $function = $LonaDB->FunctionManager->Create($data['function']['name'], $data['function']['content']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionCreate", [ "name" => $data['function']['name'], "content" => $data['function']['content'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "process" => $data['process']]);
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
