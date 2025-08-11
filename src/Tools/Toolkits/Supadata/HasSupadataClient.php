<?php

namespace NeuronAI\Tools\Toolkits\Supadata;

use GuzzleHttp\Client;

trait HasSupadataClient
{
    protected Client $client;

    public function getClient($key): Client
    {
        return $this->client ?? new Client([
            'base_uri' => 'https://api.supadata.ai/v1/',
            'headers' => [
                'x-api-key' => $key,
            ]
        ]);
    }
}
