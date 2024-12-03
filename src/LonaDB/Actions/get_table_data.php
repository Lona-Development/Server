<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of table data in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get table data in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the table data, including login and table details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the table data is retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $table = $lonaDB->getTableManager()->getTable($data['table']);
        if (!$table) {
            return $this->sendError($client, ErrorCode::TABLE_MISSING, $data['process']);
        }
        if (!$table->checkPermission($data['login']['name'], Permission::READ)) {
            return $this->sendError($client, ErrorCode::MISSING_PERMISSION, $data['process']);
        }
        $tableData = $table->getData();
        return $this->sendSuccess($client, $data['process'], ["data" => ($tableData === [] ? [] : $tableData)]);
    }
};