<?php

return new class {
    public function run($lona, $data, $client) : void {
        if(!$data['checkPass']['name'] || !$data['checkPass']['pass']) {
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $key = hash('sha256', $data['process'], true);
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if(!$lona->UserManager->CheckPermission($data['login']['name'], "password_check")) {
            $lona->Logger->Error("User '".$data['login']['name']."' tried to check a password without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $checkPassword = $lona->UserManager->CheckPassword($data['checkPass']['name'], $password);
        
        $response = json_encode(["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
