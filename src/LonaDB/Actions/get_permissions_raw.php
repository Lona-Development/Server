<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$lona->UserManager->CheckUser($data['user'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $permissions = $lona->UserManager->GetPermissions($data['user']);

        if($permissions === []) $response = '{ "success": true, "list": {}, "process": "'.$data['process'].' }';
        else $response = json_encode(["success" => true, "list" => $permissions, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
