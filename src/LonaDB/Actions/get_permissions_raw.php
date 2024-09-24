<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user is Administrator or Superuser
        if($LonaDB->UserManager->GetRole($data['login']['name']) !== "Superuser" && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Administrator"){
            //Create response array
            $response = json_encode(["success" => false, "err" => "not_allowed", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user exists
        if(!$LonaDB->UserManager->CheckUser($data['user'])){
            //Create response array
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Get permissions array
        $permissions = $LonaDB->UserManager->GetPermissions($data['user']);
        //Create response array
        if($permissions === []) $response = '{ "success": true, "list": {}, "role": "' . $LonaDB->UserManager->GetRole($data['user']) . '", "process": "'.$data['process'].'" }';
        else $response = json_encode(["success" => true, "list" => $permissions, "role" => $LonaDB->UserManager->GetRole($data['user']), "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
