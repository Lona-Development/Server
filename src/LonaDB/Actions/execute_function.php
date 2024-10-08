<?php

return new class {
    public function run($LonaDB, $data, $client) : bool {
        //Get function from FunctionManager
        $function = $LonaDB->FunctionManager->GetFunction($data['name']);
        //Execute function
        $response = $function->Execute($LonaDB, $data, $client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionExecute", [ "name" => $data['name'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "result" => $response, "process" => $data["process"]]);
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
