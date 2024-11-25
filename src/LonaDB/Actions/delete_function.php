<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if a user is allowed to delete functions
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "delete_function"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Delete the function
        $lonaDB->functionManager->delete($data['function']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "functionDelete", [ "name" => $data['function']['name'] ]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
