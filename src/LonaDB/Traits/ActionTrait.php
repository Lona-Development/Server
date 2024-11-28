<?php

namespace LonaDB\Traits;

use LonaDB\Enums\ErrorCode;

trait ActionTrait
{
    /**
     * Sends a response to the client and closes the socket.
     *
     * @param  mixed  $client  The client socket to send the response to.
     * @param  array  $responseArray  The response data to send.
     * @return bool Returns true if the response indicates success, false otherwise.
     */
    function sendSuccess(mixed $client, $process, array $responseArray): bool
    {
        $response = array_merge($responseArray, ['success' => true, 'process' => $process]);
        $this->send($client, $response);
        return true;
    }

    function sendError(mixed $client, ErrorCode $errorCode, mixed $process): bool
    {
        $response = ["err" => $errorCode, 'success' => false, "process" => $process];
        $this->send($client, $response);
        return false;
    }

    private function send($client, mixed $response): void
    {
        socket_write($client, json_encode($response));
        socket_close($client);
    }
}