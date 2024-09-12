<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        if(!$data['user']['name'] || !$data['user']['password']){
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $key = hash('sha256', $data['process'], true);
        $parts = explode(':', $data['user']['password']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_create")){
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to create a user without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }


        if($LonaDB->UserManager->CheckUser($data['user']['name'])){
            $response = json_encode(["success" => false, "err" => "user_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $result = $LonaDB->UserManager->CreateUser($data['user']['name'], $password);
        
        $response = json_encode(["success" => $result, "process" => $data['process']]);

        if(!$result) $response['err'] = "user_exists";

        socket_write($client, $response);
        socket_close($client);

        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userCreate", [ "name" => $data['user']['name'] ]);
    }
};
