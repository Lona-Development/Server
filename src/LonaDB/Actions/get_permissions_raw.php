<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        //Check if a user is Administrator or Superuser
        if ($lonaDB->userManager->getRole($data['login']['name']) !== "Superuser" && $lonaDB->userManager->getRole($data['login']['name']) !== "Administrator") {
            return $this->send($client, ["success" => false, "err" => "not_allowed", "process" => $data['process']]);
        }
        //Check if a user exists
        if (!$lonaDB->userManager->checkUser($data['user'])) {
            return $this->send($client,
                ["success" => false, "err" => "user_doesnt_exist", "process" => $data['process']]);
        }
        //Get permissions array
        $permissions = $lonaDB->userManager->getPermissions($data['user']);
        //Create a response array
        $response = [
            "success" => true, "list" => ($permissions == [] ? [] : $permissions), "role" => $lonaDB->userManager->getRole($data['user']),
            "process" => $data['process']
        ];
        //Send response
        return $this->send($client, $response);
    }
};
