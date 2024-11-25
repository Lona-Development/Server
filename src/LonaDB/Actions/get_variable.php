<?php

use LonaDB\Interfaces\ActionInterface;
use LonaDB\LonaDB;
use LonaDB\Traits\ActionTrait;

return new class implements ActionInterface {

    use ActionTrait;

    public function run(LonaDB $lonaDB, $data, $client) : bool {
        //Check if parameters have been set
        if (!$data['table']['name'] || !$data['variable']['name'])
            return $this->send($client, ["success" => false, "err" => "missing_parameters", "process" => $data['process']]);
        //Check if table exists
        if(!$lonaDB->tableManager->getTable($data['table']['name']))
            return $this->send($client, ["success" => false, "err" => "table_missing", "process" => $data['process']]);
        //Check if user is allowed to read in desired table
        if (!$lonaDB->tableManager->getTable($data['table']['name'])->checkPermission($data['login']['name'], "read"))
            return $this->send($client, ["success" => false, "err" => "missing_permissions", "process" => $data['process']]);
        //Get variable value
        $value = $lonaDB->tableManager->getTable($data['table']['name'])->get($data['variable']['name'], $data['login']['name']);
        //Create response array
        $response = [
            "variable" => [
                "name" => $data['variable']['name'],
                "value" => null,
            ],
            "success" => false,
            "process" => $data['process']
        ];
        //Check if there has been an error
        if (is_array($value) && isset($value['err'])) {
            $value['process'] = $data['process'];
            //Send response
            return $this->send($client, $value);
        }
        //Check if variable exists
        if ($value === null) 
            $response = [
                "success" => false,
                "err" => "variable_undefined",
                "process" => $data['process']
            ];
        else {
            $response['variable']['value'] = $value;
            $response['success'] = true;
        }
        //Send response
        return $this->send($client, $response);
    }
};
