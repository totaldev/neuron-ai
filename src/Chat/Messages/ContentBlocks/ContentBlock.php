<?php

declare(strict_types=1);

namespace NeuronAI\Chat\Messages\ContentBlocks;

use NeuronAI\Providers\SanitizeTrait;

abstract class ContentBlock implements ContentBlockInterface
{
    use SanitizeTrait;

    public function __construct(public string $content)
    {
    }

    public function accumulateContent(string $content): void
    {
        $this->content .= $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Sanitize content before returning array
        return [
            'content' => $this->sanitizeString($this->content),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
