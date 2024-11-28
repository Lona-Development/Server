<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the creation of tables in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to create a table in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to create the table, including login and table details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the table is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        // Check if the user is allowed to create tables
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "table_create"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);

        // Check if the table name has been set
        if (empty($data['table']['name']))
            return $this->send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);

        // Check if the user is trying to create a system table and if they are root
        if(str_starts_with($data['table']['name'], "system.") && $data['login']['name'] !== "root")
            return $this->send($client, ["success" => false, "err" => "not_root", "process" => $data['process']]);

        // Check if the table already exists
        $table = $lonaDB->tableManager->createTable($data['table']['name'], $data['login']['name']);
        if(!$table)
            return $this->send($client, ["success" => false, "err" => "table_exists", "process" => $data['process']]);

        $lonaDB->pluginManager->runEvent($data['login']['name'], "tableCreate", [ "name" => $data['table']['name'] ]);

        // Send a response indicating the table was created successfully
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};