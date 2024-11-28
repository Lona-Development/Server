<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of raw permissions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get raw permissions in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the permissions, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the permissions are retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if a user is Administrator or Superuser
        if ($lonaDB->userManager->getRole($data['login']['name']) !== "Superuser" && $lonaDB->userManager->getRole($data['login']['name']) !== "Administrator") {
            return $this->send($client, ["success" => false, "err" => "not_allowed", "process" => $data['process']]);
        }
        // Check if a user exists
        if (!$lonaDB->userManager->checkUser($data['user'])) {
            return $this->send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        }
        $permissions = $lonaDB->userManager->getPermissions($data['user']);
        $response = [
            "success" => true, "list" => ($permissions === [] ? [] : $permissions), "role" => $lonaDB->userManager->getRole($data['user']),
            "process" => $data['process']
        ];
        return $this->send($client, $response);
    }
};