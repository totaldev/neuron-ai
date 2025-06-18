<?php

namespace NeuronAI\RAG\Embeddings;

use GuzzleHttp\Client;

class OpenAIEmbeddingsProvider extends AbstractEmbeddingsProvider
{
    protected string $baseUri = 'https://api.openai.com/v1/embeddings';

    public function __construct(
        protected string $key,
        protected string $model,
        protected int    $dimensions = 1024
    ) {}

    public function embedText(string $text): array
    {
        $response = $this->getClient()->post('', [
            'json' => [
                'model'           => $this->model,
                'input'           => $text,
                'encoding_format' => 'float',
                'dimensions'      => $this->dimensions,
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true, JSON_UNESCAPED_UNICODE);

        return $response['data'][0]['embedding'];
    }

    public function initClient(): Client
    {
        return new Client([
            'base_uri' => $this->baseUri,
            'headers'  => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->key,
            ],
        ]);
    }
}
