<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if parameters exist
        if (!$data['table']['name'] || !$data['variable']['name']) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user has read permissions on the desired table
        if (!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if variable exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckVariable($data['variable']['name'], $data['login']['name'])){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_variable", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Delete variable
        $LonaDB->TableManager->GetTable($data['table']['name'])->Delete($data['variable']['name'], $data['login']['name']);
        //Create response array
        $response = [
            "success" => true,
            "process" => $data['process']
        ];
        //Send response and close socket
        socket_write($client, json_encode($response));
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "valueRemove", [ "name" => $data['variable']['name'] ]);
        return;
    }
};
