<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        $function = $LonaDB->FunctionManager->GetFunction($data['name']);

        $function->Execute($LonaDB, $data, $client);
    }
};
