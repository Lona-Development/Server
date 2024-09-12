<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        if (!$data['table']['name']) {
            $response = json_encode(["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if(!$LonaDB->TableManager->GetTable($data['table']['name'])) {
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        if (!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")){
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
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

        $value = $LonaDB->TableManager->GetTable($data['table']['name'])->Get($data['variable']['name'], $data['login']['name']);

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
            socket_write($client, json_encode($value));
            socket_close($client);
            return;
        }

        if ($value === null) {
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];

            socket_write($client, json_encode($response));
            socket_close($client);
            return;
        } else {
            $response['variable']['value'] = $value;
            $response['success'] = true;

            socket_write($client, json_encode($response));
            socket_close($client);
            return;
        }
    }
};
