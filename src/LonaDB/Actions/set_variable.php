<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the setting of a variable's value in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to set a variable's value in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to set the variable, including table and variable details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the variable is set successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if a parameter has been set
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value'])) {
            return $this->send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        }
        $tableName = $data['table']['name'];
        if (!$lonaDB->tableManager->getTable($tableName)) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        $table = $lonaDB->tableManager->getTable($tableName);
        // Check if user has write permissions on desired table
        if (!$table->checkPermission($data['login']['name'], "write")) {
            return $this->send($client,
                ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        }
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        $table->set($variableName, $variableValue, $data['login']['name']);
        $lonaDB->pluginManager->runEvent($data['login']['name'], "valueSet", [
            "table" => $data['table']['name'], "name" => $data['variable']['name'],
            "value" => $data['variable']['value']
        ]);
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};