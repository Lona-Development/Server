<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$data['user']['name']){
            $response = json_encode(["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->UserManager->CheckPermission($data['login']['name'], "user_delete")){
            $lona->Logger->Error("User '".$data['login']['name']."' tried to delete a user without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->UserManager->CheckUser($data['user']['name'])){
            $response = json_encode(["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $result = $lona->UserManager->DeleteUser($data['user']['name'], $data['user']['password']);
        
        $response = json_encode(["success" => $result, "process" => $data['process']]);

        if(!$result) $response['err'] = "user_doesnt_exist";

        $server->send($fd, $response);
        $server->close($fd);
    }
};