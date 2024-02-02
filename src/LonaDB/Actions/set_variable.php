<?php

return new class {
    public function run($lona, $data, $server, $fd) {
        if(!$data['table']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->TableManager->GetTable($data['table']['name'])) {
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if (!$lona->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "write")){
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$data['variable']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_variable_name", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$data['variable']['value']) {   
            $response = json_encode(["success" => false, "err" => "bad_variable_value", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }
    
        if(!$lona->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], 'write')){
            $response = json_encode(["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $lona->TableManager->GetTable($data['table']['name'])->Set($data['variable']['name'], $data['variable']['value'], $data['login']['name']);

        $response = json_encode(["success" => true, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
