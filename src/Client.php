<?php
namespace BatchRequest\Client;


use BatchRequest\Client\Message\Response;
use GuzzleHttp\Psr7\Request;
use BatchRequest\Client\Message\Request as BatchRequest;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    /** @var  string */
    private $boundary;

    /** @var  string */
    private $batchRequestHost;

    /** @var  BatchRequest */
    private $batchRequest;

    public function send($batchUrl, array $headers = [], array $subRequests = [])
    {
        if (sizeof($subRequests) < 1) {
            throw new \Exception("Sub requests are not found.");
        }
        $this->batchRequestHost = parse_url($batchUrl, PHP_URL_HOST);
        $this->boundary = md5(rand(0, 1000000));

        $batchRequest = $this->getBatchRequest($batchUrl, $headers, $subRequests);
        return $this->getBatchResponse($batchRequest);
    }

    private function getBatchRequest($batchUrl, array $headers = [], array $subRequests = [])
    {
        $body = $this->getBatchRequestBody($subRequests);
        $batchRequest = new BatchRequest("POST", $batchUrl, $headers, $body);
        $batchRequest->setSubRequests($subRequests);
        $batchRequest = $batchRequest->withAddedHeader(
            'Content-Type',
            sprintf(
                'multipart/batch; type="application/http;version=%s"; boundary=%s',
                $batchRequest->getProtocolVersion(), $this->boundary)
        );
        $this->batchRequest = $batchRequest;
        return $batchRequest;
    }

    private function getBatchRequestBody($subRequests = [])
    {
        $data = [];
        foreach ($subRequests as $key => $subRequest) {
            $data[] = $this->getSubRequestAsString($key, $subRequest);
        }

        $batchRequestBody = "--" . $this->boundary . PHP_EOL;
        $batchRequestBody .= implode("--" . $this->boundary . PHP_EOL, $data);
        $batchRequestBody .= "--" . $this->boundary . "--" . PHP_EOL;
        return $batchRequestBody;
    }

    private function getSubRequestAsString($key, Request $request)
    {
        $data[] = $this->getBatchHeaderForSubResponse($key, $request);
        $data[] = $this->getSubRequestHeaderAsString($request);
        if ($request->getBody()->getSize() > 0) {
            $data[] = $request->getBody()->getContents();
        }
        return implode(PHP_EOL . PHP_EOL, $data) . PHP_EOL;
    }

    private function getBatchHeaderForSubResponse($key, Request $request)
    {
        $data =
            [
                'Content-Type: application/http;version=' . $request->getProtocolVersion(),
                'Content-Transfer-Encoding: binary',
                'Content-ID: ' . sprintf("<%s-%s@%s>", $this->boundary, $key, $this->batchRequestHost)
            ];
        return implode(PHP_EOL, $data);

    }

    private function getSubRequestHeaderAsString(Request $request)
    {
        $headerData = [];
        $headerData[] = strtoupper($request->getMethod()) . ' ' . $request->getUri();
        foreach ($request->getHeaders() as $key => $value) {
            $headerData[] = sprintf("%s:%s", $key, implode(";", $value));
        }
        $headerString = implode(PHP_EOL, $headerData);

        return $headerString;
    }

    private function getBatchResponse(Request $batchRequest)
    {
        $client = new GuzzleClient();
        $batchResponse = new Response();
        $response = $client->send($batchRequest);
        $subResponsesString = $this->parseResponseBody($response->getBody()->getContents());
        $subRequestKeys = array_keys($this->batchRequest->getSubRequests());
        $i = 0;
        foreach ($subResponsesString as $subResponseString) {
            $subResponse = $this->getSubResponse(ltrim($subResponseString));
            $batchResponse->addSubResponse($subRequestKeys[$i], $subResponse);
        }
        return $batchResponse;
    }

    private function parseResponseBody($body)
    {
        $delimiter = "--" . $this->boundary . "--";
        $body = current(explode($delimiter, $body));
        $delimiter = "--" . $this->boundary;
        $subResponseData = explode($delimiter, $body);
        $subResponseData = array_filter($subResponseData, function ($data) {
            return strlen($data) > 0;
        });
        return $subResponseData;

    }

    private function getSubResponse($responseString)
    {
        $responseString = trim($responseString);
        $data = explode(PHP_EOL . PHP_EOL, $responseString, 2);
        $subResponseBodyString = $data[1];
        $subResponse = \GuzzleHttp\Psr7\parse_response($subResponseBodyString);
        return $subResponse;
    }
}