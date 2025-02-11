<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafeArray;

/**
 * Handles the creation of users in LonaDB by implementing the ActionInterface.
 */
return new class extends Action {

    /**
     * Executes the action to create a user in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to create the user, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool             True, if the user is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $username = $data['user']['name'] ?? null;
        $encryptedPassword = $data['user']['password'] ?? null;
        $processId = $data['process'] ?? null;

        if (!$username || !$encryptedPassword) {
            return $this->sendError($client, ErrorCode::MISSING_ARGUMENTS, $processId);
        }

        // Hash the process ID to derive the encryption key
        $key = hash('sha256', $processId, true);
        [$ivHex, $ciphertextHex] = explode(':', $encryptedPassword) + [null, null];
        $iv = hex2bin($ivHex);
        $ciphertext = hex2bin($ciphertextHex);

        // Decrypt the password
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if (!$password) {
            return $this->sendError($client, ErrorCode::DECRYPTION_FAILED, $processId);
        }

        $userManager = $lonaDB->getUserManager();

        if (!$userManager->checkPermission($data['login']['name'], Permission::USER_CREATE)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $processId);
        }

        if ($userManager->checkUser($username)) {
            return $this->sendError($client, ErrorCode::USER_EXISTS, $processId);
        }

        $result = $userManager->createUser($username, $password);
        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::USER_CREATE->value, ThreadSafeArray::fromArray(["name" => $username]));

        if (!$result) {
            return $this->sendError($client, ErrorCode::USER_EXISTS, $processId);
        }
        return $this->sendSuccess($client, $data['process'], []);
    }
};
