<?php

namespace LonaDB\Traits;

trait ActionTrait
{
    /**
     * Sends a response to the client and closes the socket.
     *
     * @param mixed $client The client socket to send the response to.
     * @param array $responseArray The response data to send.
     * @return bool Returns true if the response indicates success, false otherwise.
     */
    function send(mixed $client, array $responseArray): bool
    {
        $response = json_encode($responseArray);
        socket_write($client, $response);
        socket_close($client);
        return $responseArray['success'];
    }
}