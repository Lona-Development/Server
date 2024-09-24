<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Check if parameters have been set
        if (!$data['table']['name'] || !$data['variable']['name']) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name'])) {
            //Create response array
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Check if user is allowed to read in desired table
        if (!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")){
            //Create response array
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
            //Send response and close socket
            socket_write($client, $response);
            socket_close($client);
            return;
        }
        //Get variable value
        $value = $LonaDB->TableManager->GetTable($data['table']['name'])->Get($data['variable']['name'], $data['login']['name']);
        //Create response array
        $response = [
            "variable" => [
                "name" => $data['variable']['name'],
                "value" => null,
            ],
            "success" => false,
            "process" => $data['process']
        ];
        //Check if there has been an error
        if (is_array($value) && isset($value['err'])) {
            $value['process'] = $data['process'];
            //Send response and close socket
            socket_write($client, json_encode($value));
            socket_close($client);
            return;
        }
        //Check if variable exists
        if ($value === null) {
            //Create response array
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];
            //Send response and close socket
            socket_write($client, json_encode($response));
            socket_close($client);
            return;
        } else {
            $response['variable']['value'] = $value;
            $response['success'] = true;
            //Send response and close socket
            socket_write($client, json_encode($response));
            socket_close($client);
            return;
        }
    }
};
