<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the creation of users in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to create a user in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to create the user, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the user is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if username and password have been set
        if (!$data['user']['name'] || !$data['user']['password']) {
            return $this->send($client,
                ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        }

        // Hash process ID
        $key = hash('sha256', $data['process'], true);

        // Split encrypted password from IV
        $parts = explode(':', $data['user']['password']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);

        // Decrypt password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Check if the user is allowed to create new users
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "user_create")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }

        // Check if a user with that name already exists
        if ($lonaDB->userManager->checkUser($data['user']['name'])) {
            return $this->send($client, ["success" => false, "err" => "user_exist", "process" => $data['process']]);
        }

        $result = $lonaDB->userManager->createUser($data['user']['name'], $password);

        $lonaDB->pluginManager->runEvent($data['login']['name'], "userCreate", ["name" => $data['user']['name']]);

        return $this->send($client, ["success" => $result, "process" => $data['process']]);
    }
};