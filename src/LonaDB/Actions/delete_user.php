<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        if(!$data['user']['name']){
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "user_delete")){
            $LonaDB->Logger->Error("User '".$data['login']['name']."' tried to delete a user without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$LonaDB->UserManager->CheckUser($data['user']['name'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $result = $LonaDB->UserManager->DeleteUser($data['user']['name'], $data['user']['password']);
        
        $response = json_encode(["success" => $result, "process" => $data['process']]);

        if(!$result) $response['err'] = "user_doesnt_exist";

        socket_write($client, $response);
        socket_close($client);

        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "userDelete", [ "name" => $data['table']['name'] ]);
    }
};
