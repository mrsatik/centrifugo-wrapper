<?php

namespace mrsatik\CentrifugoWrapper;
{
    function time() {
        return 'time';
    }
}

namespace mrsatik\CentrifugoWrapperTest;

use mrsatik\CentrifugoWrapper\Client\Wrapper;
use Centrifugo\Exceptions\CentrifugoException;
use Centrifugo\Response;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use mrsatik\CentrifugoWrapper\CentrifugoWrapper;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class TestCentrifugoWrapper
 *
 * @package mrsatik\CentrifugoWrapperTest
 */
class CentrifugoWrapperTest extends TestCase
{
    /**
     * @see CentrifugoWrapper::publish()
     *
     * @throws ReflectionException
     */
    public function testPublish()
    {
        $expected = '123';

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['publish'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->exactly(2))
        ->method('publish')
        ->willReturn($expected);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->publish('channel', ['data']));

        $mockDriver->method('publish')->willThrowException(new CentrifugoException('exc'));
        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertFalse($wrapper->publish('channel', ['data']));
    }

    /**
     * @see CentrifugoWrapper::presence()
     *
     * @throws ReflectionException
     */
    public function testPresence()
    {
        $expected = '322';

        $mockResponse = $this->getMockBuilder(Response::class)
        ->setMethods(['getDecodedBody'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockResponse->expects($this->once())
        ->method('getDecodedBody')
        ->willReturn($expected);

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['presence'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->exactly(2))
        ->method('presence')
        ->willReturn($mockResponse);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->presence('channel'));

        $mockDriver->method('presence')->willThrowException(new CentrifugoException('exc'));
        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertFalse($wrapper->presence('channel'));
    }


    /**
     * @see CentrifugoWrapper::broadcastRequest()
     *
     * @dataProvider broadcastRequestDataProvider
     *
     * @param $inputParams
     * @param $batchResponse
     * @param $expected
     *
     * @throws ReflectionException
     */
    public function testBroadcastRequest($inputParams, $batchResponse, $expected)
    {
        $sendBatchRequestResult = [];
        foreach ($batchResponse as $response) {
            $mockResponse = $this->getMockBuilder(Response::class)
                ->setMethods(['isError', 'getDecodedBody'])
                ->disableOriginalConstructor()
                ->getMock();

            $mockResponse->method('isError')->willReturn($response['errorResponse']);
            $mockResponse->method('getDecodedBody')->willReturn($response['responseBody']);
            $sendBatchRequestResult[] = $mockResponse;
        }

        $mockDriver = $this->getMockBuilder(Wrapper::class)
            ->setMethods(['request', 'sendBroadcastRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockDriver->method('request')->willReturn('someResponse');
        $mockDriver->method('sendBroadcastRequest')->willReturn($sendBatchRequestResult);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->broadcastRequest($inputParams));
    }

    public function broadcastRequestDataProvider()
    {
        return [
            [
                [
                    ['data' => ['data' => ['data1'], 'channel' => 'test']],
                    [],
                    ['data' => ['data' => ['data2'], 'channel' => 'test2']],
                ],
                [
                    ['errorResponse' => false, 'responseBody' => 'foo',],
                    ['errorResponse' => false, 'responseBody' => 'bar']
                ],
                ['foo', 'bar']
            ],
            [
                [
                    ['data' => ['data' => ['data1'], 'channel' => 'test']],
                ],
                [
                    ['errorResponse' => true, 'responseBody' => 'foo',],
                    ['errorResponse' => false, 'responseBody' => 'bar']
                ],
                ['bar'],
            ],
            [
                [],
                [],
                []
            ],
            [
                [
                    []
                ],
                [],
                []
            ],
        ];
    }

    /**
     * @see CentrifugoWrapper::isUserOnline()
     *
     * @dataProvider isUserOnlineDataProvider
     *
     * @param $userId
     * @param $getDecodedBodyReponse
     * @param $expected
     *
     * @throws ReflectionException
     */
    public function testIsUserOnline($userId, $getDecodedBodyReponse, $expected)
    {
        $mockResponse = $this->getMockBuilder(Response::class)
        ->setMethods(['getDecodedBody'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockResponse->method('getDecodedBody')->willReturn($getDecodedBodyReponse);

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['presence'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->method('presence')->willReturn($mockResponse);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->isUserOnline($userId));

        $mockDriver->method('presence')->willThrowException(new CentrifugoException('exc'));
        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertFalse($wrapper->isUserOnline($userId));
    }

    public function isUserOnlineDataProvider()
    {
        return [
            [
                123,
                [],
                false
            ],
            [
                123,
                ['data' => 3, 'channel' => 'channel'],
                false
            ],
            [
                123,
                ['data' => ['somedata'], 'channel' => 'channel'],
                false
            ],
            [
                123,
                ['data' => [['user' => 'userId']], 'channel' => 'channel'],
                false
            ],
            [
                123,
                ['data' => [['user' => '123']], 'channel' => 'channel'],
                true
            ],
            [
                123,
                ['data' => [['user' => 123]], 'channel' => 'channel'],
                true
            ],
        ];
    }

    /**
     * @see CentrifugoWrapper::removeChannel()
     *
     * @throws ReflectionException
     */
    public function testRemoveChannel()
    {
        $expected = 123;

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['unsubscribe'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->exactly(2))
        ->method('unsubscribe')
        ->willReturn($expected);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->removeChannel('channel', 'userId'));

        $mockDriver->method('unsubscribe')->willThrowException(new CentrifugoException('exc'));
        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertFalse($wrapper->removeChannel('channel', 'userId'));
    }

    /**
     * @param $config
     * @param $clientParams
     * @param $userId
     * @param $expected
     *
     * @throws ReflectionException
     * @see CentrifugoWrapper::getClientParams()
     *
     * @dataProvider getClientParamsDataProvider
     *
     */
    public function testGetClientParams($config, $clientParams, $userId, $expected)
    {
        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['generateClientToken'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->method('generateClientToken')->willReturn('token');

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'clientParams', $clientParams);
        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);
        $wrapper = self::setProperty($wrapper, 'config', $config);
        $wrapper = self::setProperty($wrapper, 'logger', $this->getLogger());

        $this->assertEquals($expected, $wrapper->getClientParams($userId));
    }

    public function getClientParamsDataProvider()
    {
        return [
            [
                [],
                ['123'],
                1,
                '["123"]'
            ],
            [
                [],
                [],
                1,
                '[]'
            ],
            [
                [
                    'clientConfig' => []
                ],
                [],
                0,
                '[]'
            ],
            [
                [
                    'clientConfig' => [
                        'url' => 'http://192.168.83.131:8000/connection',
                    ]
                ],
                [],
                0,
                '{"url":"http:\/\/192.168.83.131:8000\/connection"}'
            ],
            [
                [
                    'clientConfig' => []
                ],
                [],
                1,
                '{"user":"1","info":null,"token":"token","timestamp":"time"}'
            ],
            [
                [
                    'clientConfig' => [
                        'refreshEndpoint' => 'http://127.0.0.1/api/push/refresh/',
                    ]
                ],
                [],
                1,
                '{"user":"1","info":null,"token":"token","timestamp":"time","refreshEndpoint":"http:\/\/127.0.0.1\/api\/push\/refresh\/"}'
            ],
            [
                [
                    'clientConfig' => [
                        'authEndpoint' => 'http://127.0.0.1/api/push/check/',
                    ]
                ],
                [],
                1,
                '{"user":"1","info":null,"token":"token","timestamp":"time","authEndpoint":"http:\/\/127.0.0.1\/api\/push\/check\/"}'
            ],
            [
                [
                    'clientConfig' => [
                        'url' => 'http://192.168.83.131:8000/connection',
                        'authEndpoint' => 'http://127.0.0.1/api/push/check/',
                        'refreshEndpoint' => 'http://127.0.0.1/api/push/refresh/',
                        'tokenInfo' => '',
                    ]
                ],
                [],
                1,
                '{"url":"http:\/\/192.168.83.131:8000\/connection","user":"1","info":null,"token":"token","timestamp":"time","refreshEndpoint":"http:\/\/127.0.0.1\/api\/push\/refresh\/","authEndpoint":"http:\/\/127.0.0.1\/api\/push\/check\/"}'
            ],
        ];
    }


    /**
     * @see CentrifugoWrapper::getChannelToken()
     *
     * @throws ReflectionException
     */
    public function testGetChannelToken()
    {
        $expected = '123';

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['generateChannelSign'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->once())
        ->method('generateChannelSign')
        ->willReturn($expected);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertEquals($expected, $wrapper->getChannelToken('clientHash', 'channel'));
    }

    /**
     * @see CentrifugoWrapper::getClientToken()
     *
     * @throws ReflectionException
     */
    public function testGetClientToken()
    {
        $expected = '123';

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['generateClientToken'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->once())
        ->method('generateClientToken')
        ->willReturn($expected);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertEquals($expected, $wrapper->getClientToken('clientHash', 'channel'));
    }

    /**
     * @see CentrifugoWrapper::getRequest()
     *
     * @throws ReflectionException
     */
    public function testGetRequest()
    {
        $expected = '123';

        $mockDriver = $this->getMockBuilder(Wrapper::class)
        ->setMethods(['request'])
        ->disableOriginalConstructor()
        ->getMock();

        $mockDriver->expects($this->once())
        ->method('request')
        ->willReturn($expected);

        $wrapper = new ReflectionClass(CentrifugoWrapper::class);
        $wrapper = $wrapper->newInstanceWithoutConstructor();

        $wrapper = self::setProperty($wrapper, 'driver', $mockDriver);

        $this->assertEquals($expected, $wrapper->getRequest('clientHash', ['channel']));
    }

    private function getLogger()
    {
        return $this->getMockBuilder(Logger::class)
        ->setMethodsExcept(['error'])
        ->disableOriginalConstructor()
        ->getMock();
    }

    /**
     * В объекте $obj устанавливаем protected свойству $property значение $data
     *
     * @param object $obj
     * @param string $property
     * @param mixed $data
     *
     * @return object
     *
     * @throws ReflectionException
     */
    public static function setProperty($obj, $property, $data)
    {
        $property = new ReflectionProperty($obj, $property);
        $property->setAccessible(true);
        $property->setValue($obj, $data);

        return $obj;
    }
}
