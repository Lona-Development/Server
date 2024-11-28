<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of table data in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get table data in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the table data, including login and table details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the table data is retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if table exists
        if (!$lonaDB->tableManager->getTable($data['table'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        // Check if the user has read permissions on the desired table
        if (!$lonaDB->tableManager->getTable($data['table'])->checkPermission($data['login']['name'], "read")) {
            return $this->send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        }
        // Get a table data array
        $tableData = $lonaDB->tableManager->getTable($data['table'])->getData();
        // Create a response array
        $response = ["success" => true, "data" => ($tableData === [] ? [] : $tableData), "process" => $data['process']];
        return $this->send($client, $response);
    }
};