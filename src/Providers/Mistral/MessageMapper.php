<?php

declare(strict_types=1);

namespace NeuronAI\Providers\Mistral;

use NeuronAI\Chat\Messages\ContentBlocks\AudioContent;
use NeuronAI\Chat\Messages\ContentBlocks\ContentBlockInterface;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\ContentBlocks\ReasoningContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ProviderException;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\SanitizeTrait;
use NeuronAI\Tools\ToolInterface;

use function array_map;
use function uniqid;
use function json_encode;
use function array_filter;

class MessageMapper implements MessageMapperInterface
{
    use SanitizeTrait;

    protected array $mapping = [];

    public function map(array $messages): array
    {
        $this->mapping = [];

        foreach ($messages as $message) {
            match ($message::class) {
                Message::class,
                UserMessage::class,
                AssistantMessage::class => $this->mapMessage($message),
                ToolCallMessage::class => $this->mapToolCall($message),
                ToolResultMessage::class => $this->mapToolsResult($message),
                default => throw new ProviderException('Unknown message type ' . $message::class),
            };
        }

        return $this->mapping;
    }

    protected function mapMessage(Message $message): void
    {
        $this->mapping[] = [
            'role'    => $message->getRole(),
            'content' => $this->mapBlocks($message->getContentBlocks()),
        ];
    }

    protected function mapBlocks(array $blocks): array
    {
        return array_filter(array_map($this->mapContentBlock(...), $blocks));
    }

    /**
     * @throws ProviderException
     */
    protected function mapContentBlock(ContentBlockInterface $block): array
    {
        return match ($block::class) {
            TextContent::class => [
                'type' => 'text',
                'text' => $this->sanitizeString($block->content),
            ],
            ReasoningContent::class => [
                'type'     => 'thinking',
                'thinking' => [
                    'type' => 'text',
                    'text' => $this->sanitizeString($block->content),
                ],
            ],
            ImageContent::class => $this->mapImageBlock($block),
            FileContent::class => $this->mapDocumentBlock($block), // File map DocumentChunk on Mistral API
            AudioContent::class => $this->mapAudioBlock($block),
            default => throw new ProviderException('Mistral does not support content block type: ' . $block::class),
        };
    }

    protected function mapImageBlock(ImageContent $block): array
    {
        return [
            'type'      => 'image_url',
            'image_url' => [
                'url' => $this->sanitizeString($block->content),
            ],
        ];
    }

    protected function mapDocumentBlock(FileContent $block): array
    {
        return [
            'type'          => 'document_url',
            'document_url'  => $this->sanitizeString($block->content),
            'document_name' => $this->sanitizeString($block->filename ?? "attachment-" . uniqid('', true) . ".pdf"),
        ];
    }

    protected function mapAudioBlock(AudioContent $block): array
    {
        return [
            'type'        => 'input_audio',
            'input_audio' => $this->sanitizeString($block->content),
        ];
    }

    protected function mapToolCall(ToolCallMessage $message): void
    {
        $item = [
            'role'       => MessageRole::ASSISTANT,
            'tool_calls' => array_map(fn(ToolInterface $tool): array => [
                'id'       => $this->sanitizeString($tool->getCallId()),
                'type'     => 'function',
                'function' => [
                    'name' => $this->sanitizeString($tool->getName()),
                    ...($tool->getInputs() === [] ? [] : ['arguments' => json_encode($this->sanitizeForJson($tool->getInputs()), JSON_THROW_ON_ERROR)]),
                ],
            ], $message->getTools()),
        ];

        $contents = $this->mapBlocks($message->getContentBlocks());
        if ($contents !== []) {
            $item['content'] = $contents;
        }

        $this->mapping[] = $item;
    }

    protected function mapToolsResult(ToolResultMessage $message): void
    {
        foreach ($message->getTools() as $tool) {
            $this->mapping[] = [
                'role'         => MessageRole::TOOL,
                'tool_call_id' => $this->sanitizeString($tool->getCallId()),
                'content'      => $this->sanitizeString((string)$tool->getResult()),
            ];
        }
    }
}
