<?php

namespace mrsatik\CentrifugoWrapper\Client;

use Centrifugo\Clients\HttpClient as OrigHttpClient;
use Centrifugo\Response;

class HttpClient extends OrigHttpClient
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function sendBroadcastRequest(Request $request): Response
    {
        return $this->sendRequest($request);
    }
}