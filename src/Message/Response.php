<?php
namespace BatchRequest\Client\Message;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response extends GuzzleResponse
{
    private $subResponses = [];

    /**
     * @return GuzzleResponse[]
     */
    public function getSubResponses()
    {
        return $this->subResponses;
    }

    /**
     * @param GuzzleResponse[] $subResponses
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
