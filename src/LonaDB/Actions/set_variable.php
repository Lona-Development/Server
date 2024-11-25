<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if a parameter has been set
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value']))
            return $this->send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Grab table name
        $tableName = $data['table']['name'];
        //Check if table exists
        if (!$lonaDB->tableManager->getTable($tableName))
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Get table instance
        $table = $lonaDB->tableManager->getTable($tableName);
        //Check if user has write permissions on desired table
        if (!$table->checkPermission($data['login']['name'], "write"))
            return $this->send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        //Prepare variable
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        //Push to table
        $table->set($variableName, $variableValue, $data['login']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "valueSet", [ "table" => $data['table']['name'], "name" => $data['variable']['name'], "value" => $data['variable']['value'] ]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
