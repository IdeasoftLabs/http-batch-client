<?php
namespace BatchRequest\Client\Message;

use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Request extends GuzzleRequest
{
    private $subRequests = [];

    /**
     * @return array
     */
    public function getSubRequests()
    {
        return $this->subRequests;
    }

    /**
     * @param array $subRequests
     */
    public function setSubRequests($subRequests)
    {
        $this->subRequests = $subRequests;
    }

    public function addSubRequest($key, GuzzleRequest $request)
    {
        $this->subRequests[$key] = $request;
    }
}