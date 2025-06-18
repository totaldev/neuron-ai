<?php

namespace NeuronAI\RAG\Embeddings;

use GuzzleHttp\Client;

class VoyageEmbeddingsProvider extends AbstractEmbeddingsProvider
{
    protected string $baseUri = 'https://api.voyageai.com/v1/embeddings';

    public function __construct(
        protected string $key,
        protected string $model,
        protected ?int   $dimensions = null
    ) {}

    public function embedText(string $text): array
    {
        $response = $this->getClient()->post('', [
            'json' => [
                'model'            => $this->model,
                'input'            => $text,
                'output_dimension' => $this->dimensions,
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

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
