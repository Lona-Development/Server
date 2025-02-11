<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of users in LonaDB.
 */
return new class extends Action {

    /**
     * Runs the action to get users in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the users, including login details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the users are retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $process = $data['process'];
        if (!$lonaDB->getUserManager()->checkPermission($data['login']['name'], Permission::findPermission("get_users"))) {
            return $this->sendError($client, ErrorCode::MISSING_PERMISSION, $process);
        }
        $users = $lonaDB->getUserManager()->listUsers();
        return $this->sendSuccess($client, $data['process'], ["users" => $users]);
    }
};
