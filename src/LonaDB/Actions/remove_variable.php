<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the removal of a variable in LonaDB.
 */
return new class extends Action {

    /**
     * Runs the action to remove a variable in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to remove the variable, including table and variable details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the variable is removed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        if (!$data['table']['name'] || !$data['variable']['name']) {
            return $this->sendError($client, ErrorCode::MISSING_PARAMETERS, $data['process']);
        }
        $tableName = $data['table']['name'];
        $table = $lonaDB->getTableManager()->getTable($tableName);

        if (!$table) {
            return $this->sendError($client, ErrorCode::TABLE_MISSING, $data['process']);
        }
        $username = $data['login']['name'];
        if (!$table->checkPermission($username, Permission::READ)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }
        if (!$table->checkVariable($data['variable']['name'], $username)) {
            return $this->sendError($client, ErrorCode::MISSING_VARIABLE, $data['process']);
        }
        $table->delete($data['variable']['name'], $username);
        $lonaDB->getPluginManager()->runEvent($username, Event::VALUE_REMOVE,
            ["table" => $tableName, "name" => $data['variable']['name']]);
        return $this->sendSuccess($client, $data['process'], []);
    }
};
