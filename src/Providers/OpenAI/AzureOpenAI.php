<?php

namespace NeuronAI\Providers\OpenAI;

use GuzzleHttp\Client;

class AzureOpenAI extends OpenAI
{
    protected string $baseUri = "https://%s/openai/deployments/%s";

    public function __construct(
        protected string $key,
        protected string $endpoint,
        protected string $model,
        protected string $version,
        protected array  $parameters = [],
    ) {
        $this->setBaseUrl();
        parent::__construct($this->key, $this->model, $this->parameters);
    }

    public function initClient(): Client
    {
        return new Client([
            'base_uri' => $this->baseUri,
            'query'    => [
                'api-version' => $this->version,
            ],
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->key,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    private function setBaseUrl()
    {
        $this->endpoint = preg_replace('/^https?:\/\/([^\/]*)\/?$/', '$1', $this->endpoint);
        $this->baseUri = sprintf($this->baseUri, $this->endpoint, $this->model);
        $this->baseUri = trim($this->baseUri, '/') . '/';
    }
}
