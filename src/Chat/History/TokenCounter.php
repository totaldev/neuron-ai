<?php

declare(strict_types=1);

namespace NeuronAI\Chat\History;

use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolCallResultMessage;
use NeuronAI\Tools\ToolInterface;

class TokenCounter implements TokenCounterInterface
{
    public function __construct(
        protected float $charsPerToken = 4.0,
        protected float $extraTokensPerMessage = 3.0
    ) {
    }

    public function count(array $messages): int
    {
        $tokenCount = 0.0;

        foreach ($messages as $message) {
            $messageChars = 0;

            // Count content characters
            $content = $message->getContent();
            if (\is_string($content)) {
                $messageChars += \strlen($content);
            } elseif ($content !== null) {
                // For arrays and other types, use JSON representation
                $messageChars += \strlen(\json_encode($content));
            }

            // Handle tool calls for AssistantMessage (excluding array content format)
            if ($message instanceof ToolCallMessage && !\is_array($content)) {
                $tools = $message->getTools();
                if ($tools !== []) {
                    // Convert tools to their JSON representation for counting
                    $toolsContent = \json_encode(\array_map(fn (ToolInterface $tool): array => $tool->jsonSerialize(), $tools));
                    $messageChars += \strlen($toolsContent);
                }
            }

            // Handle tool call results
            if ($message instanceof ToolCallResultMessage) {
                $tools = $message->getTools();
                // Add tool IDs to the count
                foreach ($tools as $tool) {
                    $serialized = $tool->jsonSerialize();
                    // Assuming tool IDs are in the serialized data
                    if (isset($serialized['id'])) {
                        $messageChars += \strlen($serialized['id']);
                    }
                }
            }

            // Count role characters
            $messageChars += \strlen($message->getRole());

            // Round up per message to ensure individual counts add up correctly
            $tokenCount += \ceil($messageChars / $this->charsPerToken);

            // Add extra tokens per message
            $tokenCount += $this->extraTokensPerMessage;
        }

        // Final round up in case extraTokensPerMessage is a float
        return (int) \ceil($tokenCount);
    }
}
