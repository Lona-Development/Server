<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if user is allowed to request a users array
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_users"))
            return $this->Send($client, ["success" => false, "err" => "missing_permission", "process" => $data['process']]);
        //Get users array
        $users = $LonaDB->UserManager->ListUsers();
        //Send response
        return $this->Send($client, ["success" => true, "users" => $users, "process" => $data['process']]);
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
