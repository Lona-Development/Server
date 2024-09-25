<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if the table name is in the parameters
        if (empty($data['table']['name']))
            return $this->Send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Check if user is allowed to delete tables
        if (!$LonaDB->UserManager->CheckPermission($data['login']['name'], "table_delete"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if the table exists
        if(!$LonaDB->TableManager->GetTable($data['table']['name']))
            return $this->Send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Check if user owns the table
        if($LonaDB->TableManager->GetTable($data['table']['name'])->GetOwner() !== $data['login']['name'] && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Administrator" && $LonaDB->UserManager->GetRole($data['login']['name']) !== "Superuser")
            return $this->Send($client, ["success" => false, "err" => "not_table_owner", "process" => $data['process']]);
        //Delete the table
        $table = $LonaDB->TableManager->DeleteTable($data['table']['name'], $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "tableDelete", [ "name" => $data['table']['name'] ]);
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
