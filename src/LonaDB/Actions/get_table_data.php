<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if table exists
        if(!$LonaDB->TableManager->GetTable($data['table']))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Check if user has read permissions on desired table
        if(!$LonaDB->TableManager->GetTable($data['table'])->CheckPermission($data['login']['name'], "read"))
            return $this->Send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        //Get table data array
        $tableData = $LonaDB->TableManager->getTable($data['table'])->GetData();
        //Create response array
        if($tableData === []) $response = ["success" => true, "data" => [], "process" => $data['process']];
        else $response = ["success" => true, "data" => $tableData, "process" => $data['process']];
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
