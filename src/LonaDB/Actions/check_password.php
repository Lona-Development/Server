<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if the user and password to check have been set
        if(!$data['checkPass']['name'] || !$data['checkPass']['pass'])
            return $this->Send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Hash the process ID
        $key = hash('sha256', $data['process'], true);
        //Split encrypted password from IV
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        //Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        //Check if user has permission to check passwords
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "password_check"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check the password
        $checkPassword = $LonaDB->UserManager->CheckPassword($data['checkPass']['name'], $password);
        //Send response
        return $this->Send($client, ["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
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
