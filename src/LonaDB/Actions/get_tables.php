<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        $user = null;
        if(!$data['user']) $user = $data['login']['name'];
        else $user = $data['user'];

        if($user !== $data['login']['name']){
            if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "get_tables")){
                $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
                socket_write($client, $response);
                socket_close($client);
                return;
            }
        }

        if(!$LonaDB->UserManager->CheckUser($user)){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $tables = $LonaDB->TableManager->ListTables($user);

        $response = json_encode(["success" => true, "tables" => $tables, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
