<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_users")){
            $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $users = $LonaDB->UserManager->ListUsers();

        $response = json_encode(["success" => true, "users" => $users, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
