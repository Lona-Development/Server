<?php

namespace LonaDB\Interfaces;

use LonaDB\LonaDB;

interface ActionInterface
{

    public function run(LonaDB $lonaDB, $data, $client) : bool;

}