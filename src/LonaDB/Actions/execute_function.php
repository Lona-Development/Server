<?php

return new class {
    public function run($LonaDB, $data, $server, $fd) : void {
        $function = $LonaDB->FunctionManager->GetFunction($data['name']);

        $function->Execute($LonaDB, $data, $server, $fd);
    }
};
