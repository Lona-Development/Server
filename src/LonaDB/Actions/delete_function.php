<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if user is allowed to delete functions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "delete_function"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Delete the function
        $function = $LonaDB->FunctionManager->Delete($data['function']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionDelete", [ "name" => $data['function']['name'] ]);
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
