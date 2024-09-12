<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        $function = $LonaDB->FunctionManager->GetFunction($data['name']);

        $function->Execute($LonaDB, $data, $client);

        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "functionExecute", [ "name" => $data['name'] ]);
    }
};
