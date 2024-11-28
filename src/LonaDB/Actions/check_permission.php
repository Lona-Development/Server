<?php

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
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if the necessary parameters have been set
        if(!$data['permission'] || !$data['permission']['name'] || !$data['permission']['user'])
            return $this->send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);

        // Check if the user has the necessary permissions
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "permission_check"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);

        // Check if the user has the permission
        $permission = $lonaDB->userManager->checkPermission($data['permission']['user'], $data['permission']['name']);

        return $this->send($client, ["success" => true, "result" => $permission, "process" => $data['process']]);
    }
};