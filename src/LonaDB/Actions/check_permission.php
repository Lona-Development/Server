<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if needed parameters have been set
        if(!$data['permission'] || !$data['permission']['name'] || !$data['permission']['user']){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user has the needed permissions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_check")) {
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to check a permission without permission");
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if the user has the permission
        $permission = $LonaDB->UserManager->CheckPermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Create response array
        $response = json_encode(["success" => true, "result" => $permission, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
