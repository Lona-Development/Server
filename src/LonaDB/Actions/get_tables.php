<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if tables for executing user have been requested
        $user = null;
        if(!$data['user']) $user = $data['login']['name'];
        else $user = $data['user'];
        //If checking for someone else
        if($user !== $data['login']['name']){
            //If user isn't allowed to request table array
            if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_tables"))
                return $this->Send($client, ["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            //Check if requested user exists
            if(!$LonaDB->UserManager->CheckUser($user))
                return $this->Send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        }
        //Get tables array
        $tables = $LonaDB->TableManager->ListTables($user);
        //Send response
        return $this->Send($client, ["success" => true, "tables" => $tables, "process" => $data['process']]);
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
