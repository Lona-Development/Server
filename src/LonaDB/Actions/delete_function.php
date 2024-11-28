<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the deletion of functions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to delete a function in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to delete the function, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the function is deleted successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if a user is allowed to delete functions
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "delete_function"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);

        $lonaDB->functionManager->delete($data['function']['name']);

        $lonaDB->pluginManager->runEvent($data['login']['name'], "functionDelete", [ "name" => $data['function']['name'] ]);

        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};