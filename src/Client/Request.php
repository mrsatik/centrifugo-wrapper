<?php

namespace mrsatik\CentrifugoWrapper\Client;

use Centrifugo\Request as OriginRequest;

class Request extends OriginRequest
{
    /**
     * {@inheritDoc}
     * @see Request::getHeaders()
     */
    public function getHeaders()
    {
        return [
            'Content-Type: application/json',
            'Authorization: apikey ' . $this->generateHashSign(),
        ];
    }

    protected function generateHashSign()
    {
        return $this->secret;
    }
}