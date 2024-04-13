<?php

namespace mrsatik\CentrifugoWrapperTest;

use PHPUnit\Framework\TestCase;
use mrsatik\CentrifugoWrapper\Client\Wrapper;
use Centrifugo\Clients\HttpClient;

/**
 * Class ClientTest
 *
 * @package mrsatik\CentrifugoWrapperTest
 */
class ClientTest extends TestCase
{
    /**
     * @param string $endpoint
     * @param string $secret
     * @param string $apikey
     * 
     * @dataProvider getClientParamsDataProvider
     */
    public function testGetClient(string $endpoint, string $secret, string $apikey)
    {
        $requestClient = new HttpClient();
        $client = new Wrapper(
            $endpoint,
            $secret,
            $apikey,
            $requestClient
        );

        $str = $client->generateClientToken(123, 'time');
        $this->assertNotEmpty($str);
        $parts = explode('.', $str);
        $this->assertCount(3, $parts);
        $str = $client->generateChannelSign(123, 'foo');
        $this->assertNotEmpty($str);
        $parts = explode('.', $str);
        $this->assertCount(3, $parts);
    }


    public function getClientParamsDataProvider()
    {
        return [
            [
                'http://test/api',
                'foo',
                'bar',
            ],
            [
                'http://test2/api',
                'bar',
                'foo',
            ],
        ];
    }
}