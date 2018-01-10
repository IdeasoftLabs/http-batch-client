<?php
namespace BatchRequest\Tests;

use BatchRequest\Client\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client $batchClient
     */
    private $batchClient;

    public function setUp()
    {
        $this->batchClient = new Client();
    }

    public function testGetBatchRequestBody()
    {
        $batchUrl = "http://test-url";
        $headers = ["Authorization" => "Bearer TOKEN"];
        $subRequests = [
            "subReq1" => new Request("GET", "http://test-url/req1", ["test_header" => "test_value"]),
            "subReq2" => new Request("POST", "http://test-url/req2", ["test_header" => "test_value"])
        ];

        /** @var \BatchRequest\Client\Message\Request $data */
        $data = $this->invokeRestrictedMethodAndProperties(
            $this->batchClient,
            'getBatchRequest',
            [$batchUrl, $headers, $subRequests],
            ["boundary" => "TEST_BOUNDARY"]
        );

        $this->assertInstanceOf('BatchRequest\Client\Message\Request', $data);
        $this->assertTrue(sizeof($data->getSubRequests()) == 2);
    }

    public function testGetSubRequestAsString()
    {
        $subRequestKey = "test-key";
        $subRequest = new Request("GET", "http://test-url", ["Authorization" => "Bearer TOKEN"], json_encode(["a", "test", "json"]));
        $data = $this->invokeRestrictedMethodAndProperties(
            $this->batchClient,
            "getSubRequestAsString",
            [$subRequestKey, $subRequest],
            ["boundary" => "TEST_BOUNDARY"]
        );

        $this->assertContains('["a","test","json"]', $data);
        $this->assertContains('Authorization:Bearer TOKEN', $data);
        $this->assertTrue(sizeof(explode(PHP_EOL . PHP_EOL, $data)) == 3);
    }

    public function testGetBatchHeaderForSubResponse()
    {
        $data = $this->invokeRestrictedMethodAndProperties($this->batchClient, 'getBatchHeaderForSubResponse',
            ["test_key", new Request("GET", "http://test-url")], ["boundary" => "test_boundary"]);

        $this->assertTrue(sizeof(explode(PHP_EOL, $data)) == 3);
        $this->assertContains("Content-Type", $data);
    }

    public function testGetSubRequestHeaderAsString()
    {
        $request = new Request("GET", "http://test-url");
        $request = $request->withAddedHeader("test-header", "test-header-value");
        $request = $request->withAddedHeader("test-header2", "test-header-value2");
        $batchClient = new Client();
        $headerString = $this->invokeRestrictedMethodAndProperties($batchClient, "getSubRequestHeaderAsString", [$request]);
        $this->assertTrue(sizeof(explode(PHP_EOL, $headerString)) == 4);
        $this->assertContains("test-header", $headerString);
        $this->assertContains("test-header-value", $headerString);
        $this->assertContains("test-header2", $headerString);
        $this->assertContains("test-header-value2", $headerString);
        $this->assertContains("http://test-url", $headerString);
    }

    public function testParseResponseBody()
    {
        $responseString = <<<EOT
--48d7fa4591ee22b613248363faa0fed8
Content-Type: application/http;version=1.0
Content-Transfer-Encoding: binaryIn-Reply-To: <48d7fa4591ee22b613248363faa0fed8@localhost>

HTTP/1.0 200 OK
Cache-Control:no-cache, private
Content-Type:application/json
X-Debug-Token:ee387c
X-Debug-Token-Link:http://localhost/_profiler/ee387c

[{"id":1,"username":"mustafaileri","email":"mi@mustafaileri.com"}]
--48d7fa4591ee22b613248363faa0fed8
Content-Type: application/http;version=1.0
Content-Transfer-Encoding: binaryIn-Reply-To: <48d7fa4591ee22b613248363faa0fed8@localhost>

HTTP/1.0 200 OK
Cache-Control:no-cache, private
Content-Type:application/json
X-Debug-Token:c80ff6
X-Debug-Token-Link:http://localhost/_profiler/c80ff6

[{"id":1,"username":"mustafaileri","email":"mi@mustafaileri.com"}]
--48d7fa4591ee22b613248363faa0fed8
Content-Type: application/http;version=1.0
Content-Transfer-Encoding: binaryIn-Reply-To: <48d7fa4591ee22b613248363faa0fed8@localhost>

HTTP/1.0 200 OK
Cache-Control:no-cache, private
Content-Type:application/json
X-Debug-Token:61598f
X-Debug-Token-Link:http://localhost/_profiler/61598f

[{"id":1,"username":"mustafaileri","email":"mi@mustafaileri.com"}]
--48d7fa4591ee22b613248363faa0fed8--
EOT;

        $parsedResponseData = $this->invokeRestrictedMethodAndProperties(
            $this->batchClient,
            "parseResponseBody",
            [$responseString],
            ["boundary" => "48d7fa4591ee22b613248363faa0fed8"]
        );
        $this->assertTrue(3 == sizeof($parsedResponseData));
    }

    public function testGetSubResponse()
    {
        $subResponseString = "Content-Type: application/http;version=1.0" . PHP_EOL;
        $subResponseString .= "Content-Transfer-Encoding: binaryIn-Reply-To: <48d7fa4591ee22b613248363faa0fed8@localhost>" . PHP_EOL;
        $subResponseString .= PHP_EOL;
        $subResponseString .= "HTTP/1.0 200 OK" . PHP_EOL;
        $subResponseString .= "Cache-Control:no-cache, private" . PHP_EOL;
        $subResponseString .= "Content-Type:application/json" . PHP_EOL;
        $subResponseString .= PHP_EOL;
        $subResponseString .= "[{\"id\":1,\"username\":\"mustafaileri\",\"email\":\"mi@mustafaileri.com\"}]";

        /** @var Response $subResponse */
        $subResponse = $this->invokeRestrictedMethodAndProperties($this->batchClient, "getSubResponse", [$subResponseString]);
        $this->assertEquals(200, $subResponse->getStatusCode());
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $subResponse);
    }

    private function invokeRestrictedMethodAndProperties($object, $methodName, $args = [], $properties = [])
    {
        $reflectionClass = new \ReflectionClass(get_class($object));
        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        foreach ($properties as $propertyKey => $value) {
            $prop = $reflectionClass->getProperty($propertyKey);
            $prop->setAccessible(true);
            $prop->setValue($object, $value);
        }
        return $method->invokeArgs($object, $args);
    }
}
