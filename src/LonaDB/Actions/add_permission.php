<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if user has been defined
        if(!$data['permission']['user'])
            return $this->Send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        //Check if user has the permission so add permissions to others
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_add"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Add permission to user
        $LonaDB->UserManager->AddPermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "permissionAdd", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
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
