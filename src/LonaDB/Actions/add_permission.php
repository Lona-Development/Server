<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the addition of permissions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

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
        if (!$data['permission']['user']) {
            return $this->send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        }

        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "permission_add")) {
            $lonaDB->userManager->addPermission($data['permission']['user'], $data['permission']['name']);
        }

        $lonaDB->pluginManager->runEvent($data['login']['name'], "permissionAdd",
            ["user" => $data['permission']['user'], "name" => $data['permission']['name']]);

        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};