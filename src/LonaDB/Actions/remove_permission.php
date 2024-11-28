<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the removal of a permission in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to remove a permission in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to remove the permission, including login and permission details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the permission is removed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if username has been set
        if(!$data['permission']['user'])
            return $this->send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        // Check if user is allowed to remove permissions
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "permission_remove"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        // Remove permission
        $lonaDB->userManager->removePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        $lonaDB->pluginManager->runEvent($data['login']['name'], "permissionRemove", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};