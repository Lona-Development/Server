<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$lona->UserManager->CheckPermission($data['login']['name'], "get_users")){
            $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $users = $lona->UserManager->ListUsers();

        $response = json_encode(["success" => true, "users" => $users, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
