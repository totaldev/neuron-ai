<?php

namespace NeuronAI;

use GuzzleHttp\Client;

trait HasGuzzleClient
{
    protected Client $client;

    abstract public function initClient(): Client;

    public function getClient(): Client
    {
        if (!isset($this->client)) {
            $this->client = $this->initClient();
        }

        return $this->client;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
