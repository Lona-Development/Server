<?php

return new class {
    public function run($lona, $data, $client) : void {
        if($data['login']['name'] !== "root" && $lona->UserManager->GetRole($data['login']['name']) !== "Administrator"){
            $response = json_encode(["success" => false, "err" => "not_allowed", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$lona->UserManager->CheckUser($data['user'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $permissions = $lona->UserManager->GetPermissions($data['user']);

        if($permissions === []) $response = '{ "success": true, "list": {}, "role": "' . $lona->UserManager->GetRole($data['user']) . '", "process": "'.$data['process'].'" }';
        else $response = json_encode(["success" => true, "list" => $permissions, "role" => $lona->UserManager->GetRole($data['user']), "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
