<?php

return new class {
    public function run($LonaDB, $data, $client) : void {
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "create_function")) {
            $response = json_encode(["success" => false, "err" => "no_permission", "process" => $data['process']]);
            socket_write($client, $response);
            socket_close($client);
            return;
        }

        $function = $LonaDB->FunctionManager->Delete($data['function']['name']);

        $response = json_encode(["success" => true, "process" => $data['process']]);
        socket_write($client, $response);
        socket_close($client);
    }
};
