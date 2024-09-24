<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if the user is allowed to create functions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "create_function")) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Create function
        $function = $LonaDB->FunctionManager->Create($data['function']['name'], $data['function']['content']);
        //Create response array
        $response = json_encode(["success" => true, "process" => $data['process']]);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionCreate", [ "name" => $data['function']['name'], "content" => $data['function']['content'] ]);
    }
};
