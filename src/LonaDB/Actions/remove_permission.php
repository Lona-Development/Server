<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafeArray;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the removal of a permission in LonaDB.
 */
return new class extends Action {
 
    /**
     * Runs the action to remove a permission in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to remove the permission, including login and permission details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the permission is removed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$data['permission']['user']) {
            return $this->sendError($client, ErrorCode::MISSING_USER, $data['process']);
        }
        if (!$lonaDB->getUserManager()->checkPermission($data['login']['name'], Permission::PERMISSION_REMOVE)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }
        $permissionName = $data['permission']['name'];
        $permissionUser = $data['permission']['user'];
        
        if(!Permission::findPermission($permissionName)) return $this->sendSuccess($client, $data['process'], []);
        $lonaDB->getUserManager()->removePermission($permissionUser, Permission::findPermission($permissionName));
        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::PERMISSION_REMOVE->value,
            ThreadSafeArray::fromArray([
                "user" => $permissionUser,
                "name" => $permissionName
            ]));
        return $this->sendSuccess($client, $data['process'], []);
    }
};
