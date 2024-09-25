<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if name has been set
        if(!$data['user']['name'])
            return $this->Send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Check if user is allowed to delete users
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_delete"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if user exists
        if(!$LonaDB->UserManager->CheckUser($data['user']['name']))
            return $this->Send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        //Delete user
        $result = $LonaDB->UserManager->DeleteUser($data['user']['name'], $data['user']['password']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userDelete", [ "name" => $data['table']['name'] ]);
        //Send response
        return $this->Send($client, ["success" => $result, "process" => $data['process']]);
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
