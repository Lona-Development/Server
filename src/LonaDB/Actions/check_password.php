<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if the user and password to check have been set
        if (!$data['checkPass']['name'] || !$data['checkPass']['pass']) {
            return $this->Send($client,
                ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        }
        //Hash the process ID
        $key = hash('sha256', $data['process'], true);
        //Split encrypted password from IV
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        //Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        //Check if the user has permission to check passwords
        if (!$lonaDB->userManager->CheckPermission($data['login']['name'], "password_check")) {
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }
        //Check the password
        $checkPassword = $lonaDB->userManager->CheckPassword($data['checkPass']['name'], $password);
        //Send response
        return $this->Send($client, ["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
    }
};
