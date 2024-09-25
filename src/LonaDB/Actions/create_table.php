<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Check if user is allowed to create tables
        if (!$LonaDB->UserManager->CheckPermission($data['login']['name'], "table_create"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if table name has been set
        if (empty($data['table']['name']))
            return $this->Send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Check if user is trying to create a system table and if they are root
        if(str_starts_with($data['table']['name'], "system.") && $data['login']['name'] !== "root")
            return $LonaDB->Sever->Send($client, ["success" => false, "err" => "not_root", "process" => $data['process']]);
        //Check if table already exists
        $table = $LonaDB->TableManager->CreateTable($data['table']['name'], $data['login']['name']);
        if(!$table)
            return $this->Send($client, ["success" => false, "err" => "table_exists", "process" => $data['process']]);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "tableCreate", [ "name" => $data['table']['name'] ]);
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
