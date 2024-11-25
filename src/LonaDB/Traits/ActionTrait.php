<?php

namespace LonaDB\Traits;

trait ActionTrait
{
    function send($client, array $responseArray): bool
    {
        //Convert a response array to a JSON object
        $response = json_encode($responseArray);
        //Send response and close socket
        socket_write($client, $response);
        socket_close($client);
        //Return state
        return $responseArray['success'];
    }

}