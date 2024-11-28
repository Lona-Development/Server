<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if table exists
        if (!$lonaDB->tableManager->getTable($data['table'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        //Check if the user has read permissions on the desired table
        if (!$lonaDB->tableManager->getTable($data['table'])->checkPermission($data['login']['name'], "read")) {
            return $this->send($client,
                ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        }
        //Get a table data array
        $tableData = $lonaDB->tableManager->getTable($data['table'])->getData();
        //Create a response array
        $response = ["success" => true, "data" => ($tableData == [] ? [] : $tableData), "process" => $data['process']];
        //Send response
        return $this->Send($client, $response);
    }
};
