<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if the table name is in the parameters
        if (empty($data['table']['name'])) {
            return $this->send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        }
        //Check if the user is allowed to delete tables
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "table_delete")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }
        //Check if the table exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        //Check if the user owns the table
        if ($lonaDB->tableManager->getTable($data['table']['name'])->getOwner() !== $data['login']['name'] && $lonaDB->userManager->GetRole($data['login']['name']) !== "Administrator" && $lonaDB->userManager->GetRole($data['login']['name']) !== "Superuser") {
            return $this->send($client,
                ["success" => false, "err" => "not_table_owner", "process" => $data['process']]);
        }
        //Delete the table
        $table = $lonaDB->tableManager->deleteTable($data['table']['name'], $data['login']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "tableDelete", ["name" => $data['table']['name']]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
