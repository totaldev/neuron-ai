<?php

namespace NeuronAI\RAG\Embeddings;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class OllamaEmbeddingsProvider extends AbstractEmbeddingsProvider
{
    public function __construct(
        protected string $model,
        protected string $url = 'http://localhost:11434/api',
        protected array  $parameters = [],
    ) {}

    public function embedText(string $text): array
    {
        $response = $this->getClient()->post('embed', [
            RequestOptions::JSON => [
                'model' => $this->model,
                'input' => $text,
                ...$this->parameters,
            ],
        ])->getBody()->getContents();

        $response = json_decode($response, true);

        return $response['embeddings'][0];
    }

    public function initClient(): Client
    {
        return new Client(['base_uri' => trim($this->url, '/') . '/']);
    }
}
