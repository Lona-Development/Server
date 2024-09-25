<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if username and password have been set
        if(!$data['user']['name'] || !$data['user']['password'])
            return $this->Send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Hash process ID
        $key = hash('sha256', $data['process'], true);
        //Split encrypted password from IV
        $parts = explode(':', $data['user']['password']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        //Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        //Check if user is allowed to create new users
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_create"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if a user with that name already exists
        if($LonaDB->UserManager->CheckUser($data['user']['name']))
            return $this->Send($client, ["success" => false, "err" => "user_exist", "process" => $data['process']]);
        //Create user
        $result = $LonaDB->UserManager->CreateUser($data['user']['name'], $password);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userCreate", [ "name" => $data['user']['name'] ]);
        //Send response
        return $this->Send($client, ["success" => $result, "process" => $data['process']]);
    }

    private function Send ($client, $responseArray) : bool {
        //Convert response array to JSON object
        $response = json_encode($responseArray);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Return state
        $bool = false;
        if($responseArray['success']) $bool = true;
        return $bool;
    }
};
