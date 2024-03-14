<?php

return new class {
    public function run($lona, $data, $client) : void {
        if (!$data['table']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$lona->TableManager->GetTable($data['table']['name'])) {
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if (!$data['variable']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_variable_name", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if (!$lona->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")) {
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$lona->TableManager->GetTable($data['table']['name'])->CheckVariable($data['variable']['name'], $data['login']['name'])){
            $response = json_encode(["success" => false, "err" => "missing_variable", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $lona->TableManager->GetTable($data['table']['name'])->Delete($data['variable']['name'], $data['login']['name']);

        $response = [
            "success" => true,
            "process" => $data['process']
        ];

        socket_write($client, json_encode($response));
        socket_close($client);
        return;
    }
};
