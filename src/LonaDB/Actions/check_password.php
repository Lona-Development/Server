<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$data['checkPass']['name'] || !$data['checkPass']['pass']) {
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->UserManager->CheckPermission($data['login']['name'], "password_check")) {
            $lona->Logger->Error("User '".$data['login']['name']."' tried to check a password without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $checkPassword = $lona->UserManager->CheckPassword($data['checkPass']['name'], $data['checkPass']['pass']);
        
        $response = json_encode(["success" => true, "passCheck" => $checkPassword, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};