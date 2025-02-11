<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Bases\Action;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafeArray;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the creation of tables in LonaDB.
 */
return new class extends Action {

    /**
     * Executes the action to create a table in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array   $data    The data required to create the table, including login and table details.
     * @param  mixed   $client  The client to send the response to.
     * @return bool             True if the table is created successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $loginName = $data['login']['name'];
        $processId = $data['process'] ?? null;
        $tableName = $data['table']['name'] ?? null;

        if (!$lonaDB->getUserManager()->checkPermission($loginName, Permission::TABLE_CREATE)) {
            return $this->sendError($client,  ErrorCode::NO_PERMISSIONS, $processId);
        }

        if (empty($tableName)) {
            return $this->sendError($client, ErrorCode::BAD_TABLE_NAME, $processId);
        }

        if (str_starts_with($tableName, 'system.') && $loginName !== 'root') {
            return $this->sendError($client, ErrorCode::NOT_ROOT, $processId);
        }

        $table = $lonaDB->getTableManager()->createTable($tableName, $loginName);
        if (!$table) {
            return $this->sendError($client,  ErrorCode::TABLE_EXISTS, $processId);
        }

        $lonaDB->getPluginManager()->runEvent($loginName, Event::TABLE_CREATE->value, ThreadSafeArray::fromArray(['name' => $tableName]));

        return $this->sendSuccess($client, $data['process'], []);
    }
};
