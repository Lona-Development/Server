<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if a user has been defined
        if (!$data['permission']['user']) {
            return $this->send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        }
        //Check if the user has the permission so add permissions to others
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "permission_add")) //Add permission to user
        {
            $lonaDB->userManager->addPermission($data['permission']['user'], $data['permission']['name']);
        }
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "permissionAdd",
            ["user" => $data['permission']['user'], "name" => $data['permission']['name']]);
        //Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};
