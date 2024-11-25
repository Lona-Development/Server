<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if a parameter has been set
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value']))
            return $this->Send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Grab table name
        $tableName = $data['table']['name'];
        //Check if table exists
        if (!$lonaDB->TableManager->GetTable($tableName))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Get table instance
        $table = $lonaDB->TableManager->GetTable($tableName);
        //Check if user has write permissions on desired table
        if (!$table->CheckPermission($data['login']['name'], "write"))
            return $this->Send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        //Prepare variable
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        //Push to table
        $table->Set($variableName, $variableValue, $data['login']['name']);
        //Run plugin event
        $lonaDB->PluginManager->RunEvent($data['login']['name'], "valueSet", [ "name" => $data['variable']['name'], "value" => $data['variable']['value'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "process" => $data['process']]);
    }
};
