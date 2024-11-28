<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
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
            return $this->sendError($client, ErrorCode::MISSING_ARGUMENTS, $data['process']);
        }
        $key = hash('sha256', $data['process'], true);
        $parts = explode(':', $data['checkPass']['pass']);
        $iv = hex2bin($parts[0]);
        $ciphertext = hex2bin($parts[1]);
        $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $userManager = $lonaDB->getUserManager();
        if (!$userManager->checkPermission($data['login']['name'], Permission::PASSWORD_CHECK)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }

        $checkPassword = $userManager->checkPassword($data['checkPass']['name'], $password);

        return $this->sendSuccess($client, $data['process'], ["passCheck" => $checkPassword]);
    }
};