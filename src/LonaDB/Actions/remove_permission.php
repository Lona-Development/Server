<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if username has been set
        if(!$data['permission']['user']) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_user", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user is allowed to remove permissions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_remove")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Remove permission
        $LonaDB->UserManager->RemovePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "permissionRemove", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
    }
};
