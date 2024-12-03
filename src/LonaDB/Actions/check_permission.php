<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the permission checking in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to check a permission in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to check the permission, including login and permission details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the permission check is successful, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $permArray = $data['permission'];

        if (!$permArray || !$permArray['name'] || !$permArray['user']) {
            return $this->sendError($client, ErrorCode::MISSING_ARGUMENTS, $data['process']);
        }

        $userManager = $lonaDB->getUserManager();
        if (!$userManager->checkPermission($data['login']['name'], Permission::PERMISSION_CHECK)) {
            return $this->sendError($client,  ErrorCode::NO_PERMISSIONS, $data['process']);
        }

        if(!Permission::findPermission($permArray['name'])) return $this->sendSuccess($client, $data['process'], ["result" => false]);
        $permission = $userManager->checkPermission($permArray['user'], Permission::findPermission($permArray['name']));

        return $this->sendSuccess($client, $data['process'], ["result" => $permission]);
    }
};