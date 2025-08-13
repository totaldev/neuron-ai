<?php

declare(strict_types=1);

namespace NeuronAI\RAG;

use JsonSerializable;

class Document implements JsonSerializable
{
    public int $chunkNumber = 0;

    public array $embedding = [];

    public ?string $hash = null;

    public string|int $id;

    public array $metadata = [];

    public float $score = 0;

    public string $sourceName = 'manual';

    public string $sourceType = 'manual';

    public function __construct(
        public string $content = '',
    ) {}

    public function addMetadata(string $key, string|int $value): Document
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): Document
    {
        $this->score = $score;

        return $this;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->getId(),
            'embedding'   => $this->getEmbedding(),
            'content'     => $this->getContent(),
            'chunkNumber' => $this->chunkNumber,
            'hash'        => $this->hash,
            'metadata'    => $this->metadata,
            'sourceType'  => $this->getSourceType(),
            'sourceName'  => $this->getSourceName(),
            'score'       => $this->getScore(),
        ];
    }
}
