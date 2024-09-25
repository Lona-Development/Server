<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if needed parameters have been set
        if(!$data['permission'] || !$data['permission']['name'] || !$data['permission']['user'])
            return $this->Send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Check if user has the needed permissions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_check"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if the user has the permission
        $permission = $LonaDB->UserManager->CheckPermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Send response
        return $this->Send($client, ["success" => true, "result" => $permission, "process" => $data['process']]);
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
