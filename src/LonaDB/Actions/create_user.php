<?php

return new class {
    public function run($lona, $data, $client) : void {
        if(!$data['user']['name'] || !$data['user']['password']){
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$lona->UserManager->CheckPermission($data['login']['name'], "user_create")){
            $lona->Logger->Error("User '".$data['login']['name']."' tried to create a user without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }


        if($lona->UserManager->CheckUser($data['user']['name'])){
            $response = json_encode(["success" => false, "err" => "user_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $result = $lona->UserManager->CreateUser($data['user']['name'], $data['user']['password']);
        
        $response = json_encode(["success" => $result, "process" => $data['process']]);

        if(!$result) $response['err'] = "user_exists";

        socket_write($client, $response);
        socket_close($client);
    }
};
