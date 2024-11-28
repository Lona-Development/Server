<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the deletion of users in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to delete a user in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to delete the user, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the user is deleted successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$data['user']['name']) {
            return $this->sendError($client,  ErrorCode::MISSING_ARGUMENTS, $data['process']);
        }

        $userManager = $lonaDB->getUserManager();
        $userName = $data['user']['name'];
        $loginName = $data['login']['name'];

        if (!$userManager->checkPermission($loginName, Permission::USER_DELETE)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }

        if (!$userManager->checkUser($userName)) {
            return $this->sendError($client, ErrorCode::USER_DOESNT_EXIST, $data['process']);
        }

        $result = $userManager->deleteUser($userName);
        $lonaDB->getPluginManager()->runEvent($loginName, Event::USER_DELETE, ["name" => $userName]);

        if (!$result) {
            return $this->sendError($client, ErrorCode::USER_DELETE_FAILED, $data['process']);
        }
        return $this->sendSuccess($client, $data['process'], []);
    }
};