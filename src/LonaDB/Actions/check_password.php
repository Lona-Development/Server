<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the password checking in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to check a password in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to check the password, including login and password details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the password check is successful, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$data['checkPass']['name'] || !$data['checkPass']['pass']) {
            return $this->send($client,
                ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        }

        // Hash the process ID
        $key = hash('sha256', $data['process'], true);

        // Split encrypted password from IV
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);

        // Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Check if the user has permission to check passwords
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "password_check")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }

        $checkPassword = $lonaDB->userManager->checkPassword($data['checkPass']['name'], $password);

        return $this->send($client, ["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
    }
};