<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if username and password have been set
        if(!$data['user']['name'] || !$data['user']['password']){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Hash process ID
        $key = hash('sha256', $data['process'], true);
        //Split encrypted password from IV
        $parts = explode(':', $data['user']['password']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        //Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        //Check if user is allowed to create new users
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_create")){
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to create a user without permission");
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if a user with that name already exists
        if($LonaDB->UserManager->CheckUser($data['user']['name'])){
            //Create response array
            $response = json_encode(["success" => false, "err" => "user_exist", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Create user
        $result = $LonaDB->UserManager->CreateUser($data['user']['name'], $password);
        //Create response array
        $response = json_encode(["success" => $result, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userCreate", [ "name" => $data['user']['name'] ]);
    }
};
