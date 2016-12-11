<?php
namespace BatchRequest\Client\Message;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response
{
    private $subResponses = [];

    /**
     * @return array
     */
    public function getSubResponses()
    {
        return $this->subResponses;
    }

    /**
     * @param array $subResponses
     */
    public function setSubResponses($subResponses)
    {
        $this->subResponses = $subResponses;
    }

    public function addSubResponse($key, GuzzleResponse $response)
    {
        $this->subResponses[$key] = $response;
    }
}