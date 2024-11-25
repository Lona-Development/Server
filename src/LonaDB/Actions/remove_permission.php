<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run($LonaDB, $data, $client) : bool {
        //Check if username has been set
        if(!$data['permission']['user'])
            return $this->Send($client, ["success" => false, "err" => "missing_user", "process" => $data['process']]);
        //Check if user is allowed to remove permissions
        if(!$LonaDB->UserManager->CheckPermission($data['login']['name'], "permission_remove"))
            return $this->Send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Remove permission
        $LonaDB->UserManager->RemovePermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Run plugin event
        $LonaDB->PluginManager->RunEvent($data['login']['name'], "permissionRemove", [ "user" => $data['permission']['user'], "name" => $data['permission']['name'] ]);
        //Send response
        return $this->Send($client, ["success" => true, "process" => $data['process']]);
    }
};
