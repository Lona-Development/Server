<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if name has been set
        if(!$data['user']['name']){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user is allowed to delete users
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_delete")){
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user exists
        if(!$LonaDB->UserManager->CheckUser($data['user']['name'])){
            //Create response array
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Delete user
        $result = $LonaDB->UserManager->DeleteUser($data['user']['name'], $data['user']['password']);
        //Create response array
        $response = json_encode(["success" => $result, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userDelete", [ "name" => $data['table']['name'] ]);
    }
};
