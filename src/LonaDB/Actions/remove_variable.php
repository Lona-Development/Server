<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if parameters exist
        if (!$data['table']['name'] || !$data['variable']['name']) {
            return $this->send($client,
                ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        }
        //Check if table exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        //Check if the user has read permissions on the desired table
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->CheckPermission($data['login']['name'], "read")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }
        //Check if a variable exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->CheckVariable($data['variable']['name'],
            $data['login']['name'])) {
            return $this->send($client,
                ["success" => false, "err" => "missing_variable", "process" => $data['process']]);
        }
        //Delete variable
        $lonaDB->tableManager->getTable($data['table']['name'])->Delete($data['variable']['name'],
            $data['login']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "valueRemove", [ "table" => $data['table']['name'], "name" => $data['variable']['name']]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
