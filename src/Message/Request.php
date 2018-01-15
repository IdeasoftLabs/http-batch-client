<?php
namespace BatchRequest\Client\Message;

use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Request extends GuzzleRequest
{
    private $subRequests = [];

    /**
     * @return GuzzleRequest[]
     */
    public function getSubRequests()
    {
        return $this->subRequests;
    }

    /**
     * @param GuzzleRequest[] $subRequests
     */
    public function setSubRequests($subRequests)
    {
        $this->subRequests = $subRequests;
    }

	/**
	 * @param string        $key
	 * @param GuzzleRequest $request
	 */
    public function addSubRequest($key, GuzzleRequest $request)
    {
        $this->subRequests[$key] = $request;
    }
}
