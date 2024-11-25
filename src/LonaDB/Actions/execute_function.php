<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Get function from FunctionManager
        $function = $lonaDB->functionManager->getFunction($data['name']);
        //Execute function
        $response = $function->execute($lonaDB, $data, $client);
        //Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "functionExecute", [ "name" => $data['name'] ]);
        //Send response
        return $this->send($client, ["success" => true, "result" => $response, "process" => $data["process"]]);
    }
};
