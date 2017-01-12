## http-batch-client [![Build Status](https://travis-ci.org/IdeasoftLabs/http-batch-client.svg?branch=master)](https://travis-ci.org/IdeasoftLabs/http-batch-client)
http-batch-client allows to combine multiple http requests into a single batch request.
This tool is useful for decrease http requests especially for api endpoints.
## About multipart/batch
Http multipart/batch is a format for packaging multiple HTTP requests in a single request. You can read this draft more info: https://tools.ietf.org/id/draft-snell-http-batch-00.html

### Installing http-batch-client
The easiest way to install http-batch-client is through composer.
```bash
composer require ideasoft/batch-request-client
```

## Important Note:
This is only a client implementation. Your api service should supports batch request.
If you are using Symfony 2.8+, you can use https://github.com/IdeasoftLabs/http-batch-bundle for server implementation.
## How to use http-batch-client
You can create a batch request client and send your requests to batch endpoint and use it via client.
```php
<?php

$client = new \BatchRequest\Client\Client();
$headers = [
    "Authorization" => "Bearer TOKEN"
];

$requests = [
    "users" => new \GuzzleHttp\Psr7\Request("GET", "http://your-api-url/users", ["Authorization" => "Bearer TOKEN"]),
    "orders" => new \GuzzleHttp\Psr7\Request("GET", "http://your-api-url/orders", ["Authorization" => "Bearer TOKEN"])
];

$data = $client->send("http://your-api-url/batch", $headers, $requests);
if ($data->getSubResponses()["users"]->getStatusCode()) {
    //sub request success for users 
}

