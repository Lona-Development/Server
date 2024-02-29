<?php

return new class {
    public function run($lona, $data, $server, $fd) : void {
        if(!$lona->TableManager->GetTable($data['table'])) {
            $response = json_encode(["success" => false, "err" => "table_missing", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        if(!$lona->TableManager->GetTable($data['table'])->CheckPermission($data['login']['name'], "read")) {
            $response = json_encode(["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
            return;
        }

        $tableData = $lona->TableManager->getTable($data['table'])->GetData();

        if($tableData === []) $response = '{ "success": true, "data": {}, "process": "'.$data['process'].'" }';
        else $response = json_encode(["success" => true, "data" => $tableData, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
