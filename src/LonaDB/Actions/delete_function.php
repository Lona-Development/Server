<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the deletion of functions in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to delete a function in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to delete the function, including login and function details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the function is deleted successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$lonaDB->getUserManager()->checkPermission($data['login']['name'], Permission::DELETE_FUNCTION)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }

        $lonaDB->getFunctionManager()->delete($data['function']['name']);

        $lonaDB->getPluginManager()->runEvent($data['login']['name'], Event::FUNCTION_DELETE,
            ["name" => $data['function']['name']]);

        return $this->sendSuccess($client, $data['process'], []);
    }
};