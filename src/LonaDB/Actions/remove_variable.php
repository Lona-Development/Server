<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the removal of a variable in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to remove a variable in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to remove the variable, including table and variable details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the variable is removed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if parameters exist
        if (!$data['table']['name'] || !$data['variable']['name']) {
            return $this->send($client, ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        }
        // Check if table exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        // Check if the user has read permissions on the desired table
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->checkPermission($data['login']['name'], "read")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }
        // Check if a variable exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->CheckVariable($data['variable']['name'], $data['login']['name'])) {
            return $this->send($client, ["success" => false, "err" => "missing_variable", "process" => $data['process']]);
        }
        // Delete variable
        $lonaDB->tableManager->getTable($data['table']['name'])->Delete($data['variable']['name'], $data['login']['name']);
        $lonaDB->pluginManager->runEvent($data['login']['name'], "valueRemove", ["table" => $data['table']['name'], "name" => $data['variable']['name']]);
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};