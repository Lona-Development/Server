<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Event;
use LonaDB\Enums\Permission;
use LonaDB\Enums\Role;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the deletion of tables in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to delete a table in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to delete the table, including login and table details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the table is deleted successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $tableName = $data['table']['name'];
        $username = $data['login']['name'];
        if (empty($tableName)) {
            return $this->sendError($client, ErrorCode::BAD_TABLE_NAME, $data['process']);
        }
        if (!$lonaDB->getUserManager()->checkPermission($username, Permission::TABLE_DELETE)) {
            return $this->sendError($client, ErrorCode::NO_PERMISSIONS, $data['process']);
        }
        $tableManager = $lonaDB->getTableManager();
        if (!$tableManager->getTable($tableName)) {
            return $this->sendError($client, ErrorCode::TABLE_MISSING, $data['process']);
        }
        $role = $lonaDB->getUserManager()->getRole($username);
        if ($tableManager->getTable($tableName)->getOwner() !== $username && $role->isNotIn([Role::ADMIN, Role::SUPERUSER])) {
            return $this->sendError($client, ErrorCode::NOT_TABLE_OWNER, $data['process']);
        }

        $tableManager->deleteTable($tableName, $username);
        $lonaDB->getPluginManager()->runEvent($username, Event::TABLE_DELETE, ["name" => $tableName]);
        return $this->sendSuccess($client, $data['process'], []);
    }
};