<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if user is allowed to delete functions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "delete_function")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Delete the function
        $function = $LonaDB->FunctionManager->Delete($data['function']['name']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionDelete", [ "name" => $data['function']['name'] ]);
    }
};
