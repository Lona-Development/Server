<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user has read permissions on desired table
        if(!$LonaDB->TableManager->GetTable($data['table'])->CheckPermission($data['login']['name'], "read")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Get table data array
        $tableData = $LonaDB->TableManager->getTable($data['table'])->GetData();
        //Create response array
        if($tableData === []) $response = '{ "success": true, "data": {}, "process": "'.$data['process'].'" }';
        else $response = json_encode(["success" => true, "data" => $tableData, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
    }
};
