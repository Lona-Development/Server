<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the addition of permissions in LonaDB.
 */
return new class extends Action {

    /**
     * Runs the action to add a permission in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to add the permission, including login and permission details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the permission is added successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $permissionUser = $data['permission']['user'];
        if (!$permissionUser) {
            return $this->sendError($client, ErrorCode::MISSING_USER, $data['process']);
        }
        $userManager = $lonaDB->getUserManager();

        if (!$userManager->checkPermission($data['login']['name'], Permission::PERMISSION_ADD)) {
            $userManager->addPermission($permissionUser, Permission::findPermission($data['permission']['name']));
        }

        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::PERMISSION_ADD,
            ["user" => $permissionUser, "name" => $data['permission']['name']]);

        return $this->sendSuccess($client, $data['process'], []);
    }
};
