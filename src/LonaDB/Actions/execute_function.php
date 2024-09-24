<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        //Get function from FunctionManager
        $function = $LonaDB->FunctionManager->GetFunction($data['name']);
        //Execute function
        $function->Execute($LonaDB, $data, $client);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionExecute", [ "name" => $data['name'] ]);
    }
};
