<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the creation of functions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to create a function in LonaDB.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param array $data The data required to create the function, including login and function details.
     * @param mixed $client The client to send the response to.
     * @return bool Returns true if the function is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        // Check if the user is allowed to create functions
        if (!$lonaDB->userManager->checkPermission($data['login']['name'], "create_function")) {
            return $this->send($client, ["success" => false, "err" => "no_permission", "process" => $data['process']]);
        }
        // Create function
        $lonaDB->functionManager->create($data['function']['name'], $data['function']['content']);
        // Run plugin event
        $lonaDB->pluginManager->runEvent($data['login']['name'], "functionCreate",
            ["name" => $data['function']['name'], "content" => $data['function']['content']]);
        // Send response
        return $this->send($client, ["success" => true, "process" => $data['process']]);
    }
};