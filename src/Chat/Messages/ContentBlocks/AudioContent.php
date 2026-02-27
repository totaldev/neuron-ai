<?php

declare(strict_types=1);

namespace NeuronAI\Chat\Messages\ContentBlocks;

use NeuronAI\Chat\Enums\ContentBlockType;
use NeuronAI\Chat\Enums\SourceType;

use NeuronAI\Providers\SanitizeTrait;
use function array_filter;

class AudioContent extends ContentBlock
{
    use SanitizeTrait;

    public function __construct(
        string $content,
        public readonly SourceType $sourceType,
        public readonly ?string $mediaType = null
    ) {
        parent::__construct($content);
    }

    public function getType(): ContentBlockType
    {
        return ContentBlockType::AUDIO;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->getType()->value,
            'source' => $this->sanitizeString($this->content),
            'source_type' => $this->sourceType->value,
            'media_type' => $this->mediaType ? $this->sanitizeString($this->mediaType) : null,
        ]);
    }
}
