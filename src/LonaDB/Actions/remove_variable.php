<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if parameters exist
        if (!$data['table']['name'] || !$data['variable']['name'])
            return $this->Send($client, ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name']))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Check if user has read permissions on the desired table
        if (!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckPermission($data['login']['name'], "read"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if variable exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name'])->CheckVariable($data['variable']['name'], $data['login']['name']))
            return $this->Send($client, ["success" => false, "err" => "missing_variable", "process" => $data['process']]);
        //Delete variable
        $LonaDB->TableManager->GetTable($data['table']['name'])->Delete($data['variable']['name'], $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "valueRemove", [ "name" => $data['variable']['name'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "process" => $data['process']]);
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
