<?php

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
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if a name has been set
        if(!$data['user']['name'])
            return $this->send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);

        // Check if a user is allowed to delete users
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "user_delete"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);

        // Check if a user exists
        if(!$lonaDB->userManager->checkUser($data['user']['name']))
            return $this->send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);

        $result = $lonaDB->userManager->deleteUser($data['user']['name']);

        $lonaDB->pluginManager->runEvent($data['login']['name'], "userDelete", [ "name" => $data['user']['name'] ]);

        return $this->send($client, ["success" => $result, "process" => $data['process']]);
    }
};