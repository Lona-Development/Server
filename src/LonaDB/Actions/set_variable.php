<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if parameter has been set
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value']))
            return $this->Send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Grab table name
        $tableName = $data['table']['name'];
        //Check if table exists
        if (!$LonaDB->TableManager->GetTable($tableName))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Get table instance
        $table = $LonaDB->TableManager->GetTable($tableName);
        //Check if user has write permissions on desired table
        if (!$table->CheckPermission($data['login']['name'], "write"))
            return $this->Send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        //Prepare variable
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        //Push to table
        $table->Set($variableName, $variableValue, $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "valueSet", [ "name" => $data['variable']['name'], "value" => $data['variable']['value'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "process" => $data['process']]);
    }

    private function Send ($client, $responseArray) : bool {
        //Convert response array to JSON object
        $response = json_encode($responseArray);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Return state
        $bool = false;
        if($responseArray['success']) $bool = true;
        return $bool;
    }
};
