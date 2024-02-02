<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$data['permission']['user']) {
            $response = json_encode(["success" => false, "err" => "missing_user", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }
        
        if(!$lona->UserManager->CheckPermission($data['login']['name'], "permission_remove")) {
            $lona->Logger->Error("User '".$data['login']['name']."' tried to add a permission without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }
        
        $lona->UserManager->RemovePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        $response = json_encode(["success" => true, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};