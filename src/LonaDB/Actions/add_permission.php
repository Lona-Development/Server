<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user has been defined
        if(!$data['permission']['user']) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_user", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user has the permission so add permissions to others
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_add")) {
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to add a permission without permission");
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Add permission to user
        $LonaDB->UserManager->AddPermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "permissionAdd", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
    }
};
