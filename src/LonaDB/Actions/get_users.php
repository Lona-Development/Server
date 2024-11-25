<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if a user is allowed to request a user's array
        if(!$lonaDB->userManager->checkPermission($data['login']['name'], "get_users"))
            return $this->send($client, ["success" => false, "err" => "missing_permission", "process" => $data['process']]);
        //Get users' array
        $users = $lonaDB->userManager->listUsers();
        //Send response
        return $this->send($client, ["success" => true, "users" => $users, "process" => $data['process']]);
    }
};
