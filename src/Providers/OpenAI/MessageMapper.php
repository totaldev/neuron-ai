<?php

declare(strict_types=1);

namespace NeuronAI\Providers\OpenAI;

use NeuronAI\Chat\Messages\ContentBlocks\ContentBlockInterface;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Enums\SourceType;
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
use function json_encode;
use function array_filter;
use function array_is_list;
use function array_merge;

class MessageMapper implements MessageMapperInterface
{
    use SanitizeTrait;

    protected array $mapping = [];

    /**
     * @throws ProviderException
     */
    public function map(array $messages): array
    {
        $this->mapping = [];

        foreach ($messages as $message) {
            $item = match ($message::class) {
                Message::class,
                UserMessage::class,
                AssistantMessage::class => $this->mapMessage($message),
                ToolCallMessage::class => $this->mapToolCall($message),
                ToolResultMessage::class => $this->mapToolsResult($message),
                default => throw new ProviderException('Unknown message type '.$message::class),
            };

            if (array_is_list($item)) {
                $this->mapping = array_merge($this->mapping, $item);
            } else {
                $this->mapping[] = $item;
            }
        }

        return $this->mapping;
    }

    protected function mapMessage(Message $message): array
    {
        return [
            'role' => $message->getRole(),
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
    protected function mapContentBlock(ContentBlockInterface $block): ?array
    {
        return match ($block::class) {
            TextContent::class => [
                'type' => 'text',
                'text' => $this->sanitizeString($block->content),
            ],
            ImageContent::class => $this->mapImageBlock($block),
            FileContent::class => $this->mapFileBlock($block),
            default => null,
        };
    }

    protected function mapImageBlock(ImageContent $block): array
    {
        $url = match ($block->sourceType) {
            SourceType::URL => $this->sanitizeString($block->content),
            SourceType::BASE64 => 'data:'.$this->sanitizeString($block->mediaType).';base64,'.$this->sanitizeString($block->content),
        };

        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => $url,
            ],
        ];
    }

    protected function mapFileBlock(FileContent $block): array
    {
        if ($block->sourceType === SourceType::URL) {
            throw new ProviderException('OpenAI does not support URL document attachments.');
        }

        return [
            'type' => 'file',
            'file' => [
                'filename' => $this->sanitizeString($block->filename),
                'file_data' => "data:{$this->sanitizeString($block->mediaType)};base64,{$this->sanitizeString($block->content)}",
            ]
        ];
    }

    protected function mapToolCall(ToolCallMessage $message): array
    {
        $toolCalls = array_map(fn (ToolInterface $tool): array => [
            'id' => $this->sanitizeString($tool->getCallId()),
            'type' => 'function',
            'function' => [
                'name' => $this->sanitizeString($tool->getName()),
                ...($tool->getInputs() === [] ? [] : ['arguments' => json_encode($this->sanitizeForJson($tool->getInputs()))]),
            ],
        ], $message->getTools());

        $item = [
            'role' => MessageRole::ASSISTANT,
            'tool_calls' => $toolCalls,
        ];

        $content = $this->mapBlocks($message->getContentBlocks());
        if ($content !== []) {
            $item['content'] = $content;
        }

        return $item;
    }

    protected function mapToolsResult(ToolResultMessage $message): array
    {
        return array_map(fn (ToolInterface $tool): array => [
            'role' => MessageRole::TOOL,
            'tool_call_id' => $this->sanitizeString($tool->getCallId()),
            'content' => $this->sanitizeString((string)$tool->getResult())
        ], $message->getTools());
    }
}
