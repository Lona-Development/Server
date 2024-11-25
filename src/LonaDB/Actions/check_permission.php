<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if the necessary parameters have been set
        if(!$data['permission'] || !$data['permission']['name'] || !$data['permission']['user'])
            return $this->send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Check if the user has the necessary permissions
        if(!$lonaDB->userManager->CheckPermission($data['login']['name'], "permission_check"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if the user has the permission
        $permission = $lonaDB->userManager->CheckPermission($data['permission']['user'], $data['permission']['name'], $data['login']['name']);
        //Send response
        return $this->send($client, ["success" => true, "result" => $permission, "process" => $data['process']]);
    }
};
