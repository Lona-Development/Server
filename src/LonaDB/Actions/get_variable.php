<?php

use LonaDB\Enums\ErrorCode;
use LonaDB\Enums\Permission;
use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

/**
 * This class implements the ActionInterface and uses the ActionTrait.
 * It defines the `run` method to handle the retrieval of a variable's value in LonaDB.
 */
return new class implements ActionInterface {

    use ActionTrait;

    /**
     * Runs the action to get a variable's value in LonaDB.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  array  $data  The data required to get the variable, including table and variable details.
     * @param  mixed  $client  The client to send the response to.
     * @return bool Returns true if the variable is retrieved successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client): bool
    {
        $process = $data['process'];
        if (!$data['table']['name'] || !$data['variable']['name']) {
            return $this->sendError($client, ErrorCode::MISSING_PARAMETERS, $process);
        }

        $table = $lonaDB->getTableManager()->getTable($data['table']['name']);

        if (!$table) {
            return $this->sendError($client, ErrorCode::TABLE_MISSING, $process);
        }
        if (!$table->checkPermission($data['login']['name'], Permission::READ)) {
            return $this->sendError($client, ErrorCode::MISSING_PERMISSION, $process);
        }

        $value = $table->get($data['variable']['name'], $data['login']['name']);

        $response = [
            "variable" => [
                "name" => $data['variable']['name'],
                "value" => null,
            ],
        ];
        if (is_array($value) && isset($value['err'])) {
            return $this->sendError($client, ErrorCode::find($value['err']), $process);
        }
      
        if ($value === null) {
            return $this->sendError($client, ErrorCode::VARIABLE_UNDEFINED, $process);
        } else {
            $response['variable']['value'] = $value;
        }
        return $this->sendSuccess($client, $process, $response);
    }
};