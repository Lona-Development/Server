<?php

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
        // Check if parameters have been set
        if (!$data['table']['name'] || !$data['variable']['name']) {
            return $this->send($client,
                ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        }
        // Check if table exists
        if (!$lonaDB->tableManager->getTable($data['table']['name'])) {
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        }
        // Check if a user is allowed to read in the desired table
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->checkPermission($data['login']['name'], "read")) {
            return $this->send($client,
                ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        }
        // Get variable value
        $value = $lonaDB->tableManager->getTable($data['table']['name'])->get($data['variable']['name'],
            $data['login']['name']);
        // Create a response array
        $response = [
            "variable" => [
                "name" => $data['variable']['name'],
                "value" => null,
            ],
            "success" => false,
            "process" => $data['process']
        ];
        // Check if there has been an error
        if (is_array($value) && isset($value['err'])) {
            $value['process'] = $data['process'];
            return $this->send($client, $value);
        }
        //Check if variable exists
        if ($value == null) 
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];
        } else {
            $response['variable']['value'] = $value;
            $response['success'] = true;
        }
        return $this->send($client, $response);
    }
};