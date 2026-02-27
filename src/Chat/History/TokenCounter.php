<?php

declare(strict_types=1);

namespace NeuronAI\Chat\History;

use JsonException;
use NeuronAI\Chat\Messages\ContentBlocks\ContentBlockInterface;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Providers\SanitizeTrait;

class TokenCounter implements TokenCounterInterface
{
    use SanitizeTrait;

    public function __construct(
        protected float $charsPerToken = 4.0,
        protected float $extraTokensPerMessage = 3.0
    ) {
    }

    /**
     * @param Message[] $messages
     */
    public function count(array $messages): int
    {
        $tokenCount = 0.0;
        $that = $this;

        foreach ($messages as $message) {
            $messageChars = 0;

            // Sanitize content blocks before encoding
            $contentBlocks = array_map(
                static fn(ContentBlockInterface $block): array => $that->sanitizeForJson($block->toArray()),
                $message->getContentBlocks()
            );
            
            try {
                $messageChars += mb_strlen(json_encode($contentBlocks, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                $messageChars += mb_strlen(json_encode($contentBlocks, JSON_PARTIAL_OUTPUT_ON_ERROR));
            }

            // Handle tool calls - sanitize inputs and callId
            if ($message instanceof ToolCallMessage) {
                foreach ($message->getTools() as $tool) {
                    $sanitizedInputs = $that->sanitizeForJson($tool->getInputs());
                    try {
                        $messageChars += mb_strlen(json_encode($sanitizedInputs, JSON_THROW_ON_ERROR));
                    } catch (JsonException $e) {
                        $messageChars += mb_strlen(json_encode($sanitizedInputs, JSON_PARTIAL_OUTPUT_ON_ERROR));
                    }

                    if ($tool->getCallId() !== null) {
                        $messageChars += mb_strlen($that->sanitizeString($tool->getCallId()));
                    }
                }
            }

            // Handle tool call results
            if ($message instanceof ToolResultMessage) {
                foreach ($message->getTools() as $tool) {
                    try {
                        $result = $tool->getResult();
                        $sanitizedResult = $that->sanitizeForJson($result);
                        $messageChars += mb_strlen(json_encode($sanitizedResult, JSON_THROW_ON_ERROR));
                    } catch (JsonException $e) {
                        $messageChars += mb_strlen($that->sanitizeString((string)$result));
                    }

                    if ($tool->getCallId() !== null) {
                        try {
                            $messageChars += mb_strlen(json_encode($that->sanitizeForJson($tool->getCallId()), JSON_THROW_ON_ERROR));
                        } catch (JsonException $e) {
                            $messageChars += mb_strlen($that->sanitizeString($tool->getCallId()));
                        }
                    }
                }
            }

            try {
                $messageChars += mb_strlen(json_encode($that->sanitizeForJson($message->getRole()), JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                $messageChars += mb_strlen($that->sanitizeString($message->getRole()));
            }

            // Round up per message to ensure individual counts add up correctly
            $tokenCount += ceil($messageChars / $this->charsPerToken);

            // Add extra tokens per message
            $tokenCount += $this->extraTokensPerMessage;
        }

        // Final round up in case extraTokensPerMessage is a float
        return (int) ceil($tokenCount);
    }
}
