<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the setting of a variable's value in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to set a variable's value in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to set the variable, including table and variable details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the variable is set successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $process = $data['process'];
        if (empty($data['table']['name']) || empty($data['variable']['name']) || empty($data['variable']['value'])) {
            return $this->sendError($client, ErrorCode::BAD_TABLE_NAME, $process);
        }
        $tableName = $data['table']['name'];
        if (!$lonaDB->getTableManager()->getTable($tableName)) {
            return $this->sendError($client, ErrorCode::TABLE_MISSING, $process);
        }
        $table = $lonaDB->getTableManager()->getTable($tableName);
        $username = $data['login']['name'];

        if (!$table->checkPermission($username, Permission::WRITE)) {
            return $this->sendError($client, ErrorCode::MISSING_PERMISSION, $process);
        }
        $variableName = $data['variable']['name'];
        $variableValue = $data['variable']['value'];
        $table->set($variableName, $variableValue, $username);
        $lonaDB->getPluginManager()->runEvent($username, Event::VALUE_SET, [
            "table" => $tableName,
            "name" => $variableName,
            "value" => $variableValue
        ]);
        return $this->sendSuccess($client, $process, []);
    }
};