<?php

return new class {
    public function run($lona, $data, $client) : void {
        if(!$lona->UserManager->CheckPermission($data['login']['name'], "get_users")){
            $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $users = $lona->UserManager->ListUsers();

        $response = json_encode(["success" => true, "users" => $users, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
