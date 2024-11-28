<?php

namespace LonaDB\Interfaces;

use LonaDB\LonaDB;

/**
 * Interface ActionInterface
 *
 * This interface defines the contract for actions that can be performed in LonaDB.
 */
interface ActionInterface
{
    /**
     * Runs the action with the provided data and client.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param array $data The data required to perform the action.
     * @param mixed $client The client to send the response to.
     * @return bool Returns true if the action is performed successfully, false otherwise.
     */
    public function run(LonaDB $lonaDB, $data, $client) : bool;
}