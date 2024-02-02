<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if($data['user'] !== $data['login']['name']){
            if(!$lona->UserManager->CheckPermission($data['login']['name'], "get_tables")){
                $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
                $server->send($fd, $response);
                $server->close($fd);
                return;
            }
        }

        if(!$lona->UserManager->CheckUser($data['user'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $tables = $lona->TableManager->ListTables($data['user']);

        $response = json_encode(["success" => true, "tables" => $tables, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
