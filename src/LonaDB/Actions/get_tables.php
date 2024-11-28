<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of tables for a user in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get tables for a user in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the tables, including login and user details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the tables are retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $user = !$data['user'] ? $data['login']['name'] : $data['user'];
        if ($user !== $data['login']['name']) {
            $userManager = $lonaDB->getUserManager();
            if (!$userManager->checkPermission($data['login']['name'], Permission::GET_TABLES)) {
                return $this->sendError($client, ErrorCode::MISSING_PERMISSION, $data['process']);
            }
            if (!$userManager->checkUser($user)) {
                return $this->sendError($client, ErrorCode::USER_DOESNT_EXIST, $data['process']);
            }
        }

        $tables = $lonaDB->getTableManager()->listTables($user);
        return $this->sendSuccess($client, $data['process'], ["tables" => $tables]);
    }
};