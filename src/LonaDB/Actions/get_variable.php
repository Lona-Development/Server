<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if parameters have been set
        if (!$data['table']['name'] || !$data['variable']['name'])
            return $this->Send($client, ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name']))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Check if user is allowed to read in desired table
        if (!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read"))
            return $this->Send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
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
            //Send response
            return $this->Send($client, $value);
        }
        //Check if variable exists
        if ($value === null) 
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];
        else {
            $response['variable']['value'] = $value;
            $response['success'] = true;
        }
        //Send response
        return $this->Send($client, $response);
    }

    private function Send ($client, $responseArray) : bool {
        //Convert response array to JSON object
        $response = json_encode($responseArray);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Return state
        $bool = false;
        if($responseArray['success']) $bool = true;
        return $bool;
    }
};
