<?php

namespace mrsatik\CentrifugoWrapper;

use Centrifugo\Request;
use Centrifugo\Response;

/**
 * Interface PusherDriverInterface
 *
 * @version 1.0.2
 * @since 1.0.0
 */
interface PusherDriverInterface
{
    /**
     * @param  string $channel
     * @param  array $data
     *
     * @return Response|false
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function publish($channel, $data);

    /**
     * @param string $channel
     *
     * @return mixed
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function presence($channel);

    /**
     * @param array $params
     * [
     *      "data" => array
     * ]
     *
     * @return array
     *
     * @version 1.0.2
     * @since 1.0.0
     */
    public function broadcastRequest(array $params): array;

    /**
     * @param int $userId
     *
     * @return false|string
     *
     * @version 1.0.2
     * @since 1.0.0
     */
    public function getClientParams($userId);

    /**
     * @param int $userId
     * @param int $timestamp
     * @param string $tokenInfo
     *
     * @return string
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function getClientToken($userId, $timestamp, $tokenInfo = '');

    /**
     * @param string $clientHash
     * @param string $channel
     * @param string $info
     *
     * @return string
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function getChannelToken($clientHash, $channel, $info = '');

    /**
     * @param int $userId
     * @param string $channel
     *
     * @return bool
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function isUserOnline($userId, $channel = '');

    /**
     * @param string $channel
     * @param int $userId
     *
     * @return mixed
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function removeChannel($channel, $userId);

    /**
     * @param string $method
     * @param array $params
     *
     * @return Request|mixed
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    public function getRequest(string $method, ?array $params = []);
}
