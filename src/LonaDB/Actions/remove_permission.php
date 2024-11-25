<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if username has been set
        if(!$data['permission']['user'])
            return $this->send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        //Check if user is allowed to remove permissions
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "permission_remove"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Remove permission
        $lonaDB->userManager->removePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "permissionRemove", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
