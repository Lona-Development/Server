<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if the user is allowed to create tables
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "table_create"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if the table name has been set
        if (empty($data['table']['name']))
            return $this->send($client, ["success" => false, "err" => "bad_table_name", "process" => $data['process']]);
        //Check if the user is trying to create a system table and if they are root
        if(str_starts_with($data['table']['name'], "system.") && $data['login']['name'] !== "root")
            return $this->send($client, ["success" => false, "err" => "not_root", "process" => $data['process']]);
        //Check if the table already exists
        $table = $lonaDB->tableManager->createTable($data['table']['name'], $data['login']['name']);
        if(!$table)
            return $this->send($client, ["success" => false, "err" => "table_exists", "process" => $data['process']]);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "tableCreate", [ "name" => $data['table']['name'] ]);
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
