<?php

namespace mrsatik\CentrifugoWrapperTest;

use PHPUnit\Framework\TestCase;
use mrsatik\CentrifugoWrapper\Client\Wrapper;
use Centrifugo\Clients\HttpClient;
use mrsatik\CentrifugoWrapper\Client\Request;

/**
 * Class RequestTest
 *
 * @package mrsatik\CentrifugoWrapperTest
 */
class RequestTest extends TestCase
{
    /**
     * @param string $endpoint
     * @param string $secret
     * @param string $apikey
     *
     * @dataProvider getClientParamsDataProvider
     */
    public function testGetClient($endpoint, $secret, $method, array $params, $expect)
    {
        $request = new Request(
            $endpoint,
            $secret,
            $method,
            $params
        );

        $result = $request->getHeaders();
        $this->assertCount(2, $result);
        $this->assertEquals('Content-Type: application/json', $result[0]);
        $this->assertEquals('Authorization: apikey ' . $expect, $result[1]);
    }

    public function getClientParamsDataProvider()
    {
        return [
            [
                'http://test/api',
                'foo',
                'bar',
                [],
                'foo'
            ],
            [
                'http://test2/api',
                'bar',
                'foo',
                [],
                'bar'
            ],
        ];
    }
}