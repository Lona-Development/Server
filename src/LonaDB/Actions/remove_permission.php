<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if username has been set
        if(!$data['permission']['user'])
            return $this->Send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        //Check if user is allowed to remove permissions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_remove"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Remove permission
        $LonaDB->UserManager->RemovePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "permissionRemove", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
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
