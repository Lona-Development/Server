<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if user is Administrator or Superuser
        if($LonaDB->UserManager->GetRole($data['login']['name']) !== "Superuser" && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Administrator")
            return $this->Send($client, ["success" => false, "err" => "not_allowed", "process" => $data['process']]);
        //Check if user exists
        if(!$LonaDB->UserManager->CheckUser($data['user']))
            return $this->Send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        //Get permissions array
        $permissions = $LonaDB->UserManager->GetPermissions($data['user']);
        //Create response array
        if($permissions === []) $response = ["success" => true, "list" => [], "role" => $LonaDB->UserManager->GetRole($data['user']), "process" => $data['process']];
        else $response = ["success" => true, "list" => $permissions, "role" => $LonaDB->UserManager->GetRole($data['user']), "process" => $data['process']];
        //Send response
        return $this->Send($client, $response);
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
