<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user is allowed to request a users array
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_users")){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Get users array
        $users = $LonaDB->UserManager->ListUsers();
        //Create response array
        $response = json_encode(["success" => true, "users" => $users, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
