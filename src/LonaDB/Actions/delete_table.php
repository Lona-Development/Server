<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if the table name is in the parameters
        if (empty($data['table']['name'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user is allowed to delete tables
        if (!$LonaDB->UserManager->CheckPermission($data['login']['name'], "table_delete")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if the table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user owns the table
        if($LonaDB->TableManager->GetTable($data['table']['name'])->GetOwner() !== $data['login']['name'] && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Administrator" && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Superuser") {
            //Create response array
            $response = json_encode(["success" => false, "err" => "not_table_owner", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Delete the table
        $table = $LonaDB->TableManager->DeleteTable($data['table']['name'], $data['login']['name']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "tableDelete", [ "name" => $data['table']['name'] ]);
    }
};
