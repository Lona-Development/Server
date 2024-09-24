<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if the user and password to check have been set
        if(!$data['checkPass']['name'] || !$data['checkPass']['pass']) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        //Hash the process ID
        $key = hash('sha256', $data['process'], true);
        //Split encrypted password from IV
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        //Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        //Check if user has permission to check passwords
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "password_check")) {
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to check a password without permission");
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check the password
        $checkPassword = $LonaDB->UserManager->CheckPassword($data['checkPass']['name'], $password);
        //Create response array
        $response = json_encode(["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
