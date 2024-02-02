<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if (!$data['table']['name']) {
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

        if (!$data['variable']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_variable_name", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if (!$lona->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")) {
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->TableManager->GetTable($data['table']['name'])->CheckVariable($data['variable']['name'], $data['login']['name'])){
            $response = json_encode(["success" => false, "err" => "missing_variable", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $lona->TableManager->GetTable($data['table']['name'])->Delete($data['variable']['name'], $data['login']['name']);

        $response = [
            "success" => true,
            "process" => $data['process']
        ];

        $server->send($fd, json_encode($response));
        $server->close($fd);
        return;
    }
};
