<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if tables for executing user have been requested
        $user = !$data['user'] ? $data['login']['name'] : $data['user'];

        //If checking for someone else
        if($user !== $data['login']['name']){
            //If the user isn't allowed to request a table array
            if(!$lonaDB->userManager->checkPermission($data['login']['name'], "get_tables"))
                return $this->send($client, ["success" => false, "err" => "missing_permission", "process" => $data['process']]);
            //Check if the requested user exists
            if(!$lonaDB->userManager->checkUser($user))
                return $this->send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        }
        //Get a table array
        $tables = $lonaDB->tableManager->listTables($user);
        //Send response
        return $this->send($client, ["success" => true, "tables" => $tables, "process" => $data['process']]);
    }
};
