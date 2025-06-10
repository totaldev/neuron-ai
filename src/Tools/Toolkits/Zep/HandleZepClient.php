<?php

namespace NeuronAI\Tools\Toolkits\Zep;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

trait HandleZepClient
{
    protected Client $client;

    protected string $url = 'https://api.getzep.com/api/v2';

    protected function initClient(): self
    {
        $this->client = new Client([
            'base_uri' => \trim($this->url, '/').'/',
            'headers' => [
                'Authorization' => "Api-Key {$this->key}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        return $this;
    }

    protected function createUser(): self
    {
        // Create the user if it doesn't exist
        try {
            $this->client->get('users/'.$this->user_id);
        } catch (\Exception $exception) {
            $this->client->post('users', [
                RequestOptions::JSON => ['user_id' => $this->user_id]
            ]);
        }

        return $this;
    }
}
