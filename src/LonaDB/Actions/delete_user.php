<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if a name has been set
        if(!$data['user']['name'])
            return $this->send($client, ["success" => false, "err" => "missing_arguments", "process" => $data['process']]);
        //Check if a user is allowed to delete users
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "user_delete"))
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        //Check if a user exists
        if(!$lonaDB->userManager->checkUser($data['user']['name']))
            return $this->send($client, ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        //Delete user
        $result = $lonaDB->userManager->deleteUser($data['user']['name']);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "userDelete", [ "name" => $data['user']['name'] ]);
        //Send response
        return $this->send($client, ["success" => $result, "process" => $data['process']]);
    }
};
