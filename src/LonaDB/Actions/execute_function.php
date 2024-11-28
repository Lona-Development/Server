<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the execution of functions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to execute a function in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to execute the function, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the function is executed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool {
        $function = $lonaDB->functionManager->getFunction($data['name']);
        $response = $function->execute($lonaDB, $data, $client);
        $lonaDB->pluginManager->runEvent($data['login']['name'], "functionExecute", [ "name" => $data['name'] ]);
        return $this->send($client, ["success" => true, "result" => $response, "process" => $data["process"]]);
    }
};