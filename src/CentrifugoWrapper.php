<?php

namespace mrsatik\CentrifugoWrapper;

use mrsatik\CentrifugoWrapper\Client\Wrapper;
use Centrifugo\Exceptions\CentrifugoException;
use mrsatik\CentrifugoWrapper\Client\HttpClient;
use Monolog\Logger;

/**
 * Class CentrifugoWrapper
 *
 * @version 1.0.2
 * @since 1.0.0
 */
class CentrifugoWrapper implements PusherDriverInterface
{
    private const EXPIRE_TIME = 600;

    /** @var Wrapper  */
    private $driver;

    /** @var array  */
    private $config;

    /** @var array  */
    private $clientParams = [];

    /** @var Logger  */
    private $logger;

    private const SECURE_PROTOCOLS_HEADERS = [
        'X-Forwarded-Proto' => ['https'], // Common
        'Front-End-Https' => ['on'], // Microsoft
    ];

    /**
     * CentrifugoDriver constructor.
     *
     * @param array $config
     * [
     *      'endpoint' => 'http://127.0.0.1:8000/api',
     *      'secret' => 'xxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx',
     *      'transportConfig' => [
     *          'http' => []
     *      ],
     *      'clientConfig' => [
     *          'url' => 'http://127.0.0.1:8000/connection',
     *          'authEndpoint' => 'https://127.0.0.1/check/',
     *          'refreshEndpoint' => 'https://127.0.0.1/refresh/',
     *          'tokenInfo' => ''
     *      ]
     * ]
     *
     * @param Logger $logger
     *
     * @version 1.0.1
     * @since 1.0.0
     */
    public function __construct(array $config, Logger $logger)
    {
        if (!isset($config['endpoint'], $config['secret'])) {
            throw new \RuntimeException('Not enough config parameters');
        }

        if (strpos($config['endpoint'], '//') === 0) {
            $config['endpoint'] = ($this->isHttps() === true ? 'https:' : 'http:') . $config['endpoint'];
        }

        $requestClient = new HttpClient();

        $this->driver = new Wrapper(
            $config['endpoint'],
            $config['secret'],
            $config['apikey'],
            $requestClient
            );

        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::publish()
     */
    public function publish($channel, $data)
    {
        try {
            return $this->driver->publish($channel, [
                'event' => $data
            ]);
        } catch (CentrifugoException $exc) {
            $this->logger->error($exc->getMessage());
        }
        return false;
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::presence()
     */
    public function presence($channel)
    {
        try {
            $response = $this->driver->presence($channel);

            return $response->getDecodedBody();
        } catch (CentrifugoException $exc) {
            $this->logger->error($exc->getMessage());
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @see \mrsatik\CentrifugoWrapper\PusherDriverInterface::broadcastRequest()
     */
    public function broadcastRequest(array $params): array
    {
        if ($params === []) {
            return [];
        }

        try {
            $requests = [];
            $data = null;

            foreach ($params as $param) {
                if (isset($param['data']['data'])) {
                    if ($data === null) {
                        $data = $param['data']['data'];
                    }
                    $requests[] = $param['data']['channel'];
                }
            }

            if ($requests === [] || $data === null) {
                return [];
            }

            $batchResponse = $this->driver->sendBroadcastRequest($requests, $data);
            $responseData = [];

            foreach ($batchResponse as $response) {
                if ($response->isError()) {
                    $this->logger->error($response->getError(), ['method' => __METHOD__,]);
                } else {
                    $responseData[] = $response->getDecodedBody();
                }
            }

            return $responseData;
        } catch (CentrifugoException $exc) {
            $this->logger->error($exc->getMessage(), [
                'method' => __METHOD__,
                'data' => ['params' => $params]
            ]);
        }

        return [];
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::isUserOnline()
     */
    public function isUserOnline($userId, $channel = '')
    {
        try {
            $response = $this->driver->presence($channel);
            $body = $response->getDecodedBody();

            if (isset($body['data'], $body['channel']) && is_array($body['data'])) {
                foreach ($body['data'] as $data) {
                    if (isset($data['user']) && (int)$data['user'] === (int)$userId) {
                        return true;
                    }
                }
            }
            return false;
        } catch (CentrifugoException $exc) {
            $this->logger->error($exc->getMessage(), array(
                'method' => __METHOD__,
                'data' => array(
                    'userId' => $userId,
                    'channel' => $channel
                )
            ));
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::removeChannel()
     */
    public function removeChannel($channel, $userId)
    {
        try {
            return $this->driver->unsubscribe($channel, $userId);
        } catch (CentrifugoException $exc) {
            $this->logger->error($exc->getMessage());
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::getClientParams()
     */
    public function getClientParams($userId)
    {
        if ($this->clientParams !== []) {
            return json_encode($this->clientParams);
        }

        if (!isset($this->config['clientConfig'])) {
            return json_encode($this->clientParams);
        }

        if (isset($this->config['clientConfig']['url'])) {
            $this->clientParams['url'] = $this->config['clientConfig']['url'];
        }

        if ((int)$userId !== 0) {
            $timestamp = time();
            if (is_numeric($timestamp)) {
                $timestamp += self::EXPIRE_TIME;
            }
            $tokenInfo = isset($this->config['clientConfig']['tokenInfo']) ? $this->config['clientConfig']['tokenInfo'] : '';

            $this->clientParams['user'] = (string) $userId;
            $this->clientParams['info'] = null;
            $this->clientParams['token'] = $this->getClientToken($userId, $timestamp, $tokenInfo);
            $this->clientParams['timestamp'] = (string) $timestamp;
        }

        if (isset($this->config['clientConfig']['refreshEndpoint'])) {
            $this->clientParams['refreshEndpoint'] = $this->config['clientConfig']['refreshEndpoint'];
        }

        if (isset($this->config['clientConfig']['authEndpoint'])) {
            $this->clientParams['authEndpoint'] = $this->config['clientConfig']['authEndpoint'];
        }

        if (isset($this->config['clientConfig']['subscribeEndpoint'])) {
            $this->clientParams['subscribeEndpoint'] = $this->config['clientConfig']['subscribeEndpoint'];
        }

        return json_encode($this->clientParams);
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::getChannelToken()
     */
    public function getChannelToken($clientHash, $channel, $info = '')
    {
        return $this->driver->generateChannelSign($clientHash, $channel, $info);
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::getClientToken()
     */
    public function getClientToken($userId, $timestamp, $tokenInfo = '')
    {
        return $this->driver->generateClientToken($userId, $timestamp, $tokenInfo);
    }

    /**
     * {@inheritDoc}
     * @see PusherDriverInterface::getRequest()
     */
    public function getRequest(string $method, ?array $params = [])
    {
        return $this->driver->request($method, $params);
    }

    /**
     * @return bool
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    private function isHttps()
    {
        if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)) {
            return true;
        }
        foreach (self::SECURE_PROTOCOLS_HEADERS as $header => $values) {
            if (\array_key_exists($header, $_SERVER) === true && ($headerValue = $_SERVER[$header]) !== null) {
                foreach ($values as $value) {
                    if (strcasecmp($headerValue, $value) === 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
