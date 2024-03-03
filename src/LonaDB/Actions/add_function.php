<?php

return new class {
    public function run($LonaDB, $data, $server, $fd) : void {
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "create_function")) {
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $function = $LonaDB->FunctionManager->Create($data['function']['name'], $data['function']['content']);

        $response = json_encode(["success" => true, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
