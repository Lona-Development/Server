<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of tables for a user in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get tables for a user in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the tables, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the tables are retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if tables for executing user have been requested
        $user = !$data['user'] ? $data['login']['name'] : $data['user'];

        // If checking for someone else
        if($user !== $data['login']['name']){
            // If the user isn't allowed to request a table array
            if(!$lonaDB->userManager->checkPermission($data['login']['name'], "get_tables"))
                return $this->send($client, ["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            // Check if the requested user exists
            if(!$lonaDB->userManager->checkUser($user))
                return $this->send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        }
        // Get a table array
        $tables = $lonaDB->tableManager->listTables($user);
        return $this->send($client, ["success" => true, "tables" => $tables, "process" => $data['process']]);
    }
};