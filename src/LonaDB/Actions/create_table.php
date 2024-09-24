<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user is allowed to create tables
        if (!$LonaDB->UserManager->CheckPermission($data['login']['name'], "table_create")) {
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to create a table without permission");
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if table name has been set
        if (empty($data['table']['name'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user is trying to create a system table and if they are root
        if(str_starts_with($data['table']['name'], "system.") && $data['login']['name'] !== "root"){
            //Create response array
            $response = json_encode(["success" => false, "err" => "not_root", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if table already exists
        $table = $LonaDB->TableManager->CreateTable($data['table']['name'], $data['login']['name']);
        if(!$table){
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_exists", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "tableCreate", [ "name" => $data['table']['name'] ]);
    }
};
