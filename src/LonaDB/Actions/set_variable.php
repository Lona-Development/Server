<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if parameter has been set
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Grab table name
        $tableName = $data['table']['name'];
        //Check if table exists
        if (!$LonaDB->TableManager->GetTable($tableName)) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Get table instance
        $table = $LonaDB->TableManager->GetTable($tableName);
        //Check if user has write permissions on desired table
        if (!$table->CheckPermission($data['login']['name'], "write")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Prepare variable
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        //Push to table
        $table->Set($variableName, $variableValue, $data['login']['name']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "valueSet", [ "name" => $data['variable']['name'], "value" => $data['variable']['value'] ]);
    }
};
