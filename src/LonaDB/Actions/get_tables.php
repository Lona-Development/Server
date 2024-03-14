<?php

return new class {
    public function run($lona, $data, $client) : void {
        if($data['user'] !== $data['login']['name']){
            if(!$lona->UserManager->CheckPermission($data['login']['name'], "get_tables")){
                $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
                socket_write($client, $response);
                socket_close($client);
                return;
            }
        }

        if(!$lona->UserManager->CheckUser($data['user'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $tables = $lona->TableManager->ListTables($data['user']);

        $response = json_encode(["success" => true, "tables" => $tables, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
