<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Role;
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
        $userManager = $lonaDB->getUserManager();
        $role = $userManager->getRole($data['login']['name']);
        if ($role->isNotIn([Role::ADMIN, Role::SUPERUSER])) {
            return $this->sendError($client, ErrorCode::NOT_ALLOWED, $data['process']);
        }

        if (!$userManager->checkUser($data['user'])) {
            return $this->sendError($client, ErrorCode::USER_DOESNT_EXIST, $data['process']);
        }
        $permissions = $userManager->getPermissions($data['user']);

        return $this->sendSuccess($client, $data['process'], [
            "list" => ($permissions === [] ? [] : $permissions),
            "role" => $userManager->getRole($data['user'])->value,
        ]);
    }
};