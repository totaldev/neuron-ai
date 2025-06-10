<?php

declare(strict_types=1);

namespace NeuronAI\Observability;

use NeuronAI\Chat\Messages\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Credits: https://github.com/sixty-nine
 */
class LogObserver implements \SplObserver
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function update(\SplSubject $subject, ?string $event = null, mixed $data = null): void
    {
        if ($event !== null) {
            $this->logger->log(LogLevel::INFO, $event, $this->serializeData($data));
        }
    }

    protected function serializeData(mixed $data): array
    {
        if ($data === null) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        if (!is_object($data)) {
            return ['data' => $data];
        }

        return match ($data::class) {
            Events\AgentError::class => [
                'error' => $data->exception->getMessage(),
            ],
            Events\Deserializing::class,
            Events\Deserialized::class => [
                'class' => $data->class
            ],
            Events\Extracted::class => [
                'message' => $data->message->jsonSerialize(),
                'schema' => $data->schema,
                'json' => $data->json,
            ],
            Events\Extracting::class,
            Events\InferenceStart::class,
            Events\MessageSaving::class,
            Events\MessageSaved::class => [
                'message' => $data->message->jsonSerialize(),
            ],
            Events\InferenceStop::class => [
                'message' => $data->message->jsonSerialize(),
                'response' => $data->response->jsonSerialize(),
            ],
            Events\InstructionsChanging::class => [
                'instructions' => $data->instructions,
            ],
            Events\InstructionsChanged::class => [
                'previous' => $data->previous,
                'current' => $data->current,
            ],
            Events\ToolCalling::class,
            Events\ToolCalled::class => [
                'tool' => $data->tool->jsonSerialize(),
            ],
            Events\Validating::class => [
                'class' => $data->class,
                'json' => $data->class,
            ],
            Events\Validated::class => [
                'class' => $data->class,
                'json' => $data->class,
                'violations' => $data->violations,
            ],
            Events\VectorStoreSearching::class => [
                'question' => $data->question->jsonSerialize(),
            ],
            Events\VectorStoreResult::class => [
                'question' => $data->question->jsonSerialize(),
                'documents' => $data->documents,
            ],
            Events\WorkflowStart::class => [
                'executionList' => $data->executionList,
            ],
            Events\WorkflowNodeStart::class => [
                'node' => $data->node,
                'input' => array_map(fn (Message $message) => $message->jsonSerialize(), $data->messages),
            ],
            Events\WorkflowNodeEnd::class => [
                'node' => $data->node,
                'lastReply' => $data->lastReply?->jsonSerialize(),
            ],
            Events\WorkflowEnd::class => [
                'lastReply' => $data->lastReply?->jsonSerialize(),
            ],
            default => [],
        };
    }
}
