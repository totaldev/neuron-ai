<?php

declare(strict_types=1);

namespace NeuronAI\Agent;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Inspector\Exceptions\InspectorException;
use NeuronAI\Agent\Events\AIInferenceEvent;
use NeuronAI\Agent\Nodes\ChatNode;
use NeuronAI\Agent\Nodes\ParallelToolNode;
use NeuronAI\Agent\Nodes\StreamingNode;
use NeuronAI\Agent\Nodes\StructuredOutputNode;
use NeuronAI\Agent\Nodes\ToolNode;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Exceptions\ToolMaxTriesException;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\HandleContent;
use NeuronAI\Chat\Messages\Stream\Adapters\StreamAdapterInterface;
use NeuronAI\Observability\EventBus;
use NeuronAI\Observability\Events\InferenceStart;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\JsonExtractor;
use NeuronAI\StructuredOutput\JsonSchema;
use NeuronAI\StructuredOutput\Validation\Validator;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Interrupt\InterruptRequest;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowInterrupt;
use Exception;
use Generator;
use Throwable;

use function count;
use function get_class;
use function implode;
use function trim;

use const PHP_EOL;

/**
 * @method AgentState resolveState()
 */
class Agent extends Workflow implements AgentInterface
{
    use ResolveState;
    use ResolveProvider;
    use HandleTools;
    use HandleContent;
    use ChatHistoryHelper;

    protected string $instructions;

    protected bool $parallelToolCalls = false;

    /**
     * @param Message|Message[] $messages
     * @throws Throwable
     * @throws WorkflowInterrupt
     * @throws InspectorException
     * @throws WorkflowException
     */
    public function chat(Message|array $messages = [], ?InterruptRequest $interrupt = null): Message
    {
        EventBus::emit('chat-start', $this);

        $messages = is_array($messages) ? $messages : [$messages];
        foreach ($messages as $message) {
            $this->addToChatHistory($this->resolveState(), $message);
        }

        // Prepare workflow nodes for chat mode
        $this->compose(
            new ChatNode($this->resolveProvider()),
        );

        // Start workflow execution (Agent IS the workflow)
        $handler = parent::start($interrupt);

        /** @var AgentState $finalState */
        $finalState = $handler->getResult();

        EventBus::emit('chat-stop', $this);

        return $finalState->getChatHistory()->getLastMessage();
    }

    /**
     * Determines whether tools should be executed in parallel.
     * Override this method to return true to enable parallel tool execution.
     *
     * Note: Parallel execution requires the pcntl extension and spatie/fork package.
     */
    public function parallelToolCalls(?bool $flag = null): bool|AgentInterface
    {
        if ($flag === null) {
            return $this->parallelToolCalls;
        }
        $this->parallelToolCalls = $flag;
        return $this;
    }

    public function resolveInstructions(): string
    {
        return $this->instructions ?? $this->instructions();
    }

    public function setInstructions(string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * Stream agent responses, optionally through a protocol adapter.
     *
     * @param Message|Message[] $messages
     * @throws InspectorException
     * @throws Throwable
     * @throws WorkflowException
     * @throws WorkflowInterrupt
     */
    public function stream(
        Message|array $messages = [],
        ?InterruptRequest $interrupt = null,
        ?StreamAdapterInterface $adapter = null,
    ): Generator {
        EventBus::emit('stream-start', $this);

        $messages = is_array($messages) ? $messages : [$messages];
        foreach ($messages as $message) {
            $this->addToChatHistory($this->resolveState(), $message);
        }

        // Prepare workflow nodes for streaming mode
        $this->compose(
            new StreamingNode($this->resolveProvider()),
        );

        // Start workflow execution
        $handler = parent::start($interrupt);

        // Stream events, optionally through adapter
        foreach ($handler->streamEvents($adapter) as $event) {
            yield $event;
        }

        EventBus::emit('stream-stop', $this);

        return $this->resolveState()->getChatHistory()->getLastMessage();
    }

    /**
     * @param Message|Message[] $messages
     * @throws AgentException
     * @throws InspectorException
     * @throws Throwable
     * @throws WorkflowException
     * @throws WorkflowInterrupt
     */
    public function structured(Message|array $messages = [], ?string $class = null, int $maxRetries = 1, ?InterruptRequest $interrupt = null): mixed
    {
        EventBus::emit('structured-start', $this);

        $messages = is_array($messages) ? $messages : [$messages];
        foreach ($messages as $message) {
            $this->addToChatHistory($this->resolveState(), $message);
        }

        // Get the output class
        $class ??= $this->getOutputClass();

        // Prepare workflow nodes for structured output mode
        $this->compose(
            new StructuredOutputNode($this->resolveProvider(), $class, $maxRetries),
        );

        // Start workflow execution (Agent IS the workflow)
        $handler = parent::start($interrupt);

        /** @var AgentState $finalState */
        $finalState = $handler->getResult();

        EventBus::emit('structured-stop', $this);

        // Return the structured output object
        return $finalState->get('structured_output');
    }

    /**
     * Async variant of structured().
     *
     * Performs the same work as structured() (schema generation, corrective
     * retries on invalid/failed responses) without blocking the caller:
     * returns a promise that settles with the deserialized output object or
     * rejects with the last error.
     *
     * Designed for use with a provider that shares a single Guzzle client
     * (CurlMultiHandler multiplexing), so several structuredAsync() calls on
     * different agent instances progress concurrently in one PHP process.
     *
     * Note: tool-call responses are not handled here (no workflow to route
     * them); if the LLM returns a tool call, the promise rejects.
     *
     * @param Message|Message[] $messages
     * @return PromiseInterface<mixed> settles with the structured output object
     */
    public function structuredAsync(Message|array $messages = [], ?string $class = null, int $maxRetries = 1): PromiseInterface
    {
        $messages = is_array($messages) ? $messages : [$messages];
        $state = $this->resolveState();
        foreach ($messages as $message) {
            $this->addToChatHistory($state, $message);
        }

        // Honor a custom start event (setStartEvent()) if one is set,
        // otherwise fall back to the default one.
        /** @var AIInferenceEvent $event */
        $event = $this->resolveStartEvent() instanceof AIInferenceEvent
            ? $this->resolveStartEvent()
            : new AIInferenceEvent($this->resolveInstructions(), $this->bootstrapTools());

        $class ??= $this->getOutputClass();

        $schema = JsonSchema::make()->generate($class);

        return $this->structuredAsyncAttempt(
            $this->resolveProvider(),
            $state,
            $event,
            $class,
            $schema,
            $maxRetries,
            '',
        );
    }

    /**
     * Prepare the agent workflow with mode-specific nodes.
     * Since Agent extends Workflow, we configure the current instance.
     *
     * @param Node|Node[] $nodes Mode-specific nodes (ChatNode, StreamingNode, etc.)
     */
    protected function compose(array|Node $nodes): void
    {
        if ($this->eventNodeMap !== []) {
            // Already composed, do nothing
            return;
        }

        $nodes = is_array($nodes) ? $nodes : [$nodes];

        // Select the appropriate ToolNode based on the parallel execution setting
        $toolNode = $this->parallelToolCalls()
            ? new ParallelToolNode($this->toolMaxTries)
            : new ToolNode($this->toolMaxTries);

        // Add nodes to this workflow instance
        $this->addNodes([
            ...$nodes,
            $toolNode,
        ]);
    }

    /**
     * Get the output class for structured output.
     * Override this method in subclasses to provide a default output class.
     *
     * @throws AgentException
     */
    protected function getOutputClass(): string
    {
        throw new AgentException('You need to specify an output class.');
    }

    protected function instructions(): string
    {
        return 'Your are a helpful and friendly AI agent built with Neuron AI PHP agentic framework.';
    }

    protected function startEvent(): Event
    {
        return new AIInferenceEvent(
            $this->resolveInstructions(),
            $this->bootstrapTools()
        );
    }

    /**
     * Extract, deserialize, and validate the LLM response.
     * Mirrors StructuredOutputNode::processResponse().
     * @throws AgentException
     * @throws InspectorException
     * @throws DeserializerException
     * @throws \ReflectionException
     */
    protected function structuredAsyncProcessResponse(Message $response, array $schema, string $class): object
    {
        EventBus::emit('structured-extracting', $this, new \NeuronAI\Observability\Events\Extracting($response));
        $json = (new JsonExtractor())->getJson($response->getContent());
        EventBus::emit('structured-extracted', $this, new \NeuronAI\Observability\Events\Extracted($response, $schema, $json));
        if ($json === null || $json === '') {
            throw new AgentException("The response does not contains a valid JSON Object.");
        }

        EventBus::emit('structured-deserializing', $this, new \NeuronAI\Observability\Events\Deserializing($class));
        $obj = Deserializer::make()->fromJson($json, $class);
        EventBus::emit('structured-deserialized', $this, new \NeuronAI\Observability\Events\Deserialized($class));

        EventBus::emit('structured-validating', $this, new \NeuronAI\Observability\Events\Validating($class, $json));
        $violations = Validator::validate($obj);
        if (count($violations) > 0) {
            EventBus::emit('structured-validated', $this, new \NeuronAI\Observability\Events\Validated($class, $json, $violations));
            throw new AgentException(PHP_EOL.'- '.implode(PHP_EOL.'- ', $violations));
        }
        EventBus::emit('structured-validated', $this, new \NeuronAI\Observability\Events\Validated($class, $json));

        return $obj;
    }

    /**
     * Promise-chained retry loop mirroring StructuredOutputNode::__invoke().
     */
    private function structuredAsyncAttempt(
        AIProviderInterface $provider,
        AgentState $state,
        AIInferenceEvent $event,
        string $class,
        array $schema,
        int $triesLeft,
        string $error,
    ): PromiseInterface {
        if (trim($error) !== '') {
            $correctionMessage = new UserMessage(
                "There was a problem in your previous response that generated the following errors".
                PHP_EOL.PHP_EOL.'- '.$error.PHP_EOL.PHP_EOL.
                "Try to generate the correct JSON structure based on the provided schema."
            );
            $this->addToChatHistory($state, $correctionMessage);
        }

        $last = $state->getChatHistory()->getLastMessage();
        EventBus::emit('inference-start', $this, new InferenceStart($last));

        $onSuccess = function (Message $response) use ($state, $event, $class, $schema, $triesLeft, $last) {
            EventBus::emit('inference-stop', $this, new \NeuronAI\Observability\Events\InferenceStop($last, $response));

            $this->addToChatHistory($state, $response);

            if ($response instanceof ToolCallMessage) {
                throw new AgentException('structuredAsync() does not support tool calls.');
            }

            try {
                return $this->structuredAsyncProcessResponse($response, $schema, $class);
            } catch (Throwable $ex) {
                // Ошибка извлечения/десериализации/валидации JSON — ретраим
                // (throw внутри then() bypass-ит rejection-обработчик того же звена,
                //  поэтому маршрутизируем через общий обработчик).
                return $this->structuredAsyncHandleError($ex, $state, $event, $class, $schema, $triesLeft);
            }
        };

        $onFailure = function (Throwable $reason) use ($state, $event, $class, $schema, $triesLeft) {
            return $this->structuredAsyncHandleError($reason, $state, $event, $class, $schema, $triesLeft);
        };

        return $provider
            ->systemPrompt($event->instructions)
            ->setTools($event->tools)
            ->structuredAsync($state->getChatHistory()->getMessages(), $class, $schema)
            ->then($onSuccess, $onFailure);
    }

    /**
     * Shared error handler for the async retry loop: non-fixable errors are
     * rethrown, everything else is retried with a corrective message until the
     * attempts run out (mirrors StructuredOutputNode's do/while).
     *
     * @return PromiseInterface retry chain
     */
    private function structuredAsyncHandleError(
        Throwable $reason,
        AgentState $state,
        AIInferenceEvent $event,
        string $class,
        array $schema,
        int $triesLeft,
    ): PromiseInterface {
        // Tool-max-tries is not fixable by retrying the model.
        if ($reason instanceof ToolMaxTriesException) {
            throw $reason;
        }

        if ($reason instanceof RequestException) {
            $message = $reason->getResponse()?->getBody()->getContents() ?? $reason->getMessage();
        } else {
            $message = $reason->getMessage();
        }

        EventBus::emit('error', $this, new \NeuronAI\Observability\Events\AgentError($reason, false));

        if ($triesLeft - 1 >= 0) {
            return $this->structuredAsyncAttempt($this->resolveProvider(), $state, $event, $class, $schema, $triesLeft - 1, $message);
        }

        throw $reason;
    }
}
