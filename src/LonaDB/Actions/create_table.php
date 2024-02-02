<?php

return new class {
    public function run($lona, $data, $server, $fd) {
        if (!$lona->UserManager->CheckPermission($data['login']['name'], "table_create")) {
            $lona->Logger->Error("User '".$data['login']['name']."' tried to create a Table without permission");
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if (empty($data['table']['name'])) {
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $table = $lona->TableManager->CreateTable($data['table']['name'], $data['login']['name']);

        if(!$table){
            $response = json_encode(["success" => false, "err" => "table_exists", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $response = json_encode(["success" => true, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
