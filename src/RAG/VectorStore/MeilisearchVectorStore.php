<?php

namespace NeuronAI\RAG\VectorStore;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use NeuronAI\HasGuzzleClient;
use NeuronAI\RAG\Document;

class MeilisearchVectorStore implements VectorStoreInterface
{
    use HasGuzzleClient;

    public function __construct(
        protected string  $indexUid,
        protected string  $host = 'http://localhost:7700',
        protected ?string $key = null,
        protected string  $embedder = 'default',
        protected int     $topK = 5,
    ) {
        try {
            $this->getClient()->get('');
        } catch (Exception $exception) {
            $this->getClient()->post(trim($host, '/') . '/indexes/', [
                RequestOptions::JSON => [
                    'uid'        => $indexUid,
                    'primaryKey' => 'id',
                ],
            ]);
        }
    }

    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents): void
    {
        $this->getClient()->put('documents', [
            RequestOptions::JSON => array_map(function (Document $document) {
                return [
                    'id'         => $document->getId(),
                    'content'    => $document->getContent(),
                    'sourceType' => $document->getSourceType(),
                    'sourceName' => $document->getSourceName(),
                    ...$document->metadata,
                    '_vectors'   => [
                        'default' => [
                            'embeddings' => $document->getEmbedding(),
                            'regenerate' => false,
                        ],
                    ],
                ];
            }, $documents),
        ]);
    }

    public function initClient(): Client
    {
        return new Client([
            'base_uri' => trim($this->host, '/') . '/indexes/' . $this->indexUid . '/',
            'headers'  => [
                'Content-Type' => 'application/json',
                ...(!is_null($this->key) ? ['Authorization' => "Bearer {$this->key}"] : []),
            ],
        ]);
    }

    public function similaritySearch(array $embedding): iterable
    {
        $response = $this->getClient()->post('search', [
            RequestOptions::JSON => [
                'vector'           => $embedding,
                'limit'            => min($this->topK, 20),
                'retrieveVectors'  => true,
                'showRankingScore' => true,
                'hybrid'           => [
                    'semanticRatio' => 1.0,
                    'embedder'      => $this->embedder,
                ],
            ],
        ])->getBody()->getContents();

        $response = json_decode($response, true);

        return array_map(static function (array $item) {
            $document = new Document($item['content']);
            $document->id = $item['id'] ?? uniqid('', true);
            $document->sourceType = $item['sourceType'] ?? null;
            $document->sourceName = $item['sourceName'] ?? null;
            $document->embedding = $item['_vectors']['default']['embeddings'];
            $document->score = $item['_rankingScore'];

            foreach ($item as $name => $value) {
                if (!in_array($name, ['_vectors', '_rankingScore', 'content', 'sourceType', 'sourceName', 'score', 'embedding', 'id'])) {
                    $document->addMetadata($name, $value);
                }
            }

            return $document;
        }, $response['hits']);
    }
}
