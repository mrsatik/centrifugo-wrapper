<?php

namespace mrsatik\CentrifugoWrapper\Client;

use Centrifugo\Centrifugo;
use mrsatik\CentrifugoWrapper\Client\Request;

class Wrapper extends Centrifugo
{
    private $apiKey;
    
    public function __construct($endpoint, $secret, $apiKey, $client)
    {
        $this->setApiKey($apiKey);
        parent::__construct($endpoint, $secret, $client);
    }
    
    public function request($method, array $params = [])
    {
        return new Request($this->endpoint, $this->apiKey, $method, $params);
    }
    
    public function sendBroadcastRequest(array $channels, array $sendData)
    {
        $requests = [
            'channels' => $channels,
            'data' => $sendData,
        ];
        $request = $this->request('broadcast', $requests);
        return $this->lastResponse = $this->client->sendBroadcastRequest($request);
    }
    
    /**
     * {@inheritDoc}
     * @see Centrifugo::generateClientToken()
     */
    public function generateClientToken($user, $timestamp, $info = '')
    {
        $header = array('typ' => 'JWT', 'alg' => 'HS256');
        $payload = array('sub' => (string) $user);
        if ($info !== '') {
            $payload['info'] = $info;
        }
        if ($timestamp) {
            $payload['exp'] = $timestamp;
        }
        $segments = [];
        $segments[] = $this->urlsafeB64Encode(json_encode($header));
        $segments[] = $this->urlsafeB64Encode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->secret);
        $segments[] = $this->urlsafeB64Encode($signature);
        return implode('.', $segments);
    }
    
    /**
     * {@inheritDoc}
     * @see Centrifugo::generateChannelSign()
     */
    public function generateChannelSign($client, $channel, $info = '')
    {
        $header = array('typ' => 'JWT', 'alg' => 'HS256');
        $payload = array('channel' => (string)$channel, 'client' => (string)$client);
        if ($info !== '') {
            $payload['info'] = $info;
        }
        $segments = [];
        $segments[] = $this->urlsafeB64Encode(json_encode($header));
        $segments[] = $this->urlsafeB64Encode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->secret);
        $segments[] = $this->urlsafeB64Encode($signature);
        return implode('.', $segments);
    }
    
    private function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    private function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
    
    private function sign($msg, $key)
    {
        return hash_hmac('sha256', $msg, $key, true);
    }
}
