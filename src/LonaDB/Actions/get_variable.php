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

        if (!$lona->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")){
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
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

        $value = $lona->TableManager->GetTable($data['table']['name'])->Get($data['variable']['name'], $data['login']['name']);

        $response = [
            "variable" => [
                "name" => $data['variable']['name'],
                "value" => null,
            ],
            "success" => false,
            "process" => $data['process']
        ];

        if (is_array($value) && isset($value['err'])) {
            $value['process'] = $data['process'];
            $server->send($fd, json_encode($value));
            $server->close($fd);
            return;
        }

        if ($value === null) {
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];

            $server->send($fd, json_encode($response));
            $server->close($fd);
            return;
        } else {
            $response['variable']['value'] = $value;
            $response['success'] = true;

            $server->send($fd, json_encode($response));
            $server->close($fd);
            return;
        }
    }
};