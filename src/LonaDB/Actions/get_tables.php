<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if tables for executing user have been requested
        $user = null;
        if(!$data['user']) $user = $data['login']['name'];
        else $user = $data['user'];
        //If checking for someone else
        if($user !== $data['login']['name']){
            //If user isn't allowed to request table array
            if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_tables")){
                //Create response array
                $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
                //Send response and close socket
                socket_write($client, $response);
                socket_close($client);
                return;
            }
            //Check if requested user exists
            if(!$LonaDB->UserManager->CheckUser($user)){
                $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
                socket_write($client, $response);
                socket_close($client);
                return;
            }
        }
        //Get tables array
        $tables = $LonaDB->TableManager->ListTables($user);
        //Create response array
        $response = json_encode(["success" => true, "tables" => $tables, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
