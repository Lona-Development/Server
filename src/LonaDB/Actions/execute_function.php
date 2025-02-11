<?php

use LonaDB\Enums\Event;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the execution of functions in LonaDB.
 */
return new class extends Action {

    /**
     * Runs the action to execute a function in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to execute the function, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the function is executed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $name = $data['name'];
        $response = $lonaDB->getFunctionManager()->getFunction($name)->execute($lonaDB, $data, $client);
        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::FUNCTION_EXECUTE, ["name" => $name]);
        return $this->sendSuccess($client, $data['process'], ["result" => $response]);
    }
};
