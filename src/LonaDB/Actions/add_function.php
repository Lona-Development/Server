<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
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
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to create the function, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the function is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$lonaDB->getUserManager()->checkPermission($data['login']['name'], Permission::CREATE_FUNCTION)) {
            return $this->sendError($client,  ErrorCode::NO_PERMISSIONS, $data['process']);
        }

        $lonaDB->getFunctionManager()->create($data['function']['name'], $data['function']['content']);

        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::FUNCTION_CREATE,
            ["name" => $data['function']['name'], "content" => $data['function']['content']]);

        return $this->sendSuccess($client, $data['process'], []);
    }
};