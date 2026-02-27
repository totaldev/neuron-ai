<?php

declare(strict_types=1);

namespace NeuronAI\Providers\AWS;

use NeuronAI\Chat\Messages\ContentBlocks\ContentBlockInterface;
use NeuronAI\Chat\Messages\ContentBlocks\ReasoningContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\SanitizeTrait;

use function array_map;
use function array_merge;
use function array_filter;

class MessageMapper implements MessageMapperInterface
{
    use SanitizeTrait;

    public function map(array $messages): array
    {
        $mapping = [];

        foreach ($messages as $message) {
            $mapping[] = match ($message::class) {
                ToolResultMessage::class => $this->mapToolCallResult($message),
                ToolCallMessage::class => $this->mapToolCall($message),
                default => $this->mapMessage($message),
            };
        }

        return $mapping;
    }

    protected function mapToolCallResult(ToolResultMessage $message): array
    {
        $toolContents = [];
        foreach ($message->getTools() as $tool) {
            $toolContents[] = [
                'toolResult' => [
                    'content' => [
                        [
                            'json' => [
                                'result' => $this->sanitizeString((string)$tool->getResult()),
                            ],
                        ]
                    ],
                    'toolUseId' => $this->sanitizeString($tool->getCallId()),
                ]
            ];
        }

        return [
            'role' => $message->getRole(),
            'content' => $toolContents,
        ];
    }

    protected function mapToolCall(ToolCallMessage $message): array
    {
        $toolCallContents = [];

        foreach ($message->getTools() as $tool) {
            $toolCallContents[] = [
                'toolUse' => [
                    'name' => $this->sanitizeString($tool->getName()),
                    'input' => $tool->getInputs(),
                    'toolUseId' => $this->sanitizeString($tool->getCallId()),
                ],
            ];
        }

        return [
            'role' => $message->getRole(),
            'content' => array_merge($this->mapBlocks($message->getContentBlocks()), $toolCallContents),
        ];
    }

    protected function mapMessage(Message $message): array
    {
        return [
            'role' => $message->getRole(),
            'content' => $this->mapBlocks($message->getContentBlocks()),
        ];
    }

    /**
     * @param ContentBlockInterface[] $blocks
     */
    protected function mapBlocks(array $blocks): array
    {
        return array_filter(array_map($this->mapContentBlock(...), $blocks));
    }

    protected function mapContentBlock(ContentBlockInterface $block): ?array
    {
        if ($block instanceof ReasoningContent) {
            return ['text' => $this->sanitizeString($block->content), 'signature' => $block->id];
        }

        if ($block instanceof TextContent) {
            return ['text' => $this->sanitizeString($block->content)];
        }

        return null;
    }
}
