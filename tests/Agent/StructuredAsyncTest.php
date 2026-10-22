<?php

declare(strict_types=1);

namespace NeuronAI\Tests\Agent;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Tests\Stubs\StructuredOutput\Summary;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Unit tests for Agent::structuredAsync() (no network).
 *
 * The provider receives a Guzzle MockHandler via HttpClientOptions::handler —
 * responses are pulled from a queue, no real HTTP is involved.
 */
final class StructuredAsyncTest extends TestCase
{
    private const string VALID_BODY = '{"summary":"Order delivery","categories":[{"id":1,"name":"Test","reason":"fits"}]}';

    private const string EMPTY_CATEGORIES_BODY = '{"summary":"Order delivery","categories":[]}';

    public function setUp(): void
    {
        // The package's autoload-dev (NeuronAI\Tests\ => tests/) is not part
        // of the consuming project's vendor autoloader, so load the stub
        // explicitly (idempotent).
        require_once dirname(__DIR__) . '/Stubs/StructuredOutput/Summary.php';

        parent::setUp();
    }

    /**
     * All attempts invalid → promise rejects with the last error.
     */
    public function testAllRetriesFailedRejects(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody('not json 1')),
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody('not json 2')),
        ]);
        $agent = $this->agent($mock);

        $caught = null;
        try {
            // maxRetries=1 → 2 attempts (initial + 1 retry), both invalid
            $agent->structuredAsync(new UserMessage('dialog'), Summary::class, 1)->wait();
        } catch (Throwable $e) {
            $caught = $e;
        }

        self::assertNotNull($caught, 'promise must reject after all retries are exhausted');
        self::assertStringContainsString('JSON', $caught->getMessage());
    }

    /**
     * Empty required array on every attempt → promise rejects with a
     * validation error about the empty array.
     */
    public function testEmptyRequiredArrayAllRetriesRejects(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::EMPTY_CATEGORIES_BODY)),
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::EMPTY_CATEGORIES_BODY)),
        ]);
        $agent = $this->agent($mock);

        $caught = null;
        try {
            $agent->structuredAsync(new UserMessage('dialog'), Summary::class, 1)->wait();
        } catch (Throwable $e) {
            $caught = $e;
        }

        self::assertNotNull($caught, 'promise must reject after retries with an empty required array');
        self::assertStringContainsString('categories', $caught->getMessage());
    }

    /**
     * Empty required array field (categories: []) → corrective retry;
     * second attempt is valid → resolves.
     */
    public function testEmptyRequiredArrayTriggersRetryThenSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::EMPTY_CATEGORIES_BODY)),
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::VALID_BODY)),
        ]);
        $agent = $this->agent($mock);

        $result = $agent->structuredAsync(new UserMessage('dialog'), Summary::class, 3)->wait();

        self::assertInstanceOf(Summary::class, $result);
        self::assertCount(1, $result->categories);
    }

    /**
     * Invalid JSON first, valid second → corrective retry, ends in success.
     */
    public function testInvalidResponseTriggersRetryThenSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody('this is not json')),
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::VALID_BODY)),
        ]);
        $agent = $this->agent($mock);

        $result = $agent->structuredAsync(new UserMessage('dialog'), Summary::class, 3)->wait();

        self::assertInstanceOf(Summary::class, $result);
        self::assertSame('Order delivery', $result->summary);
    }

    /**
     * Valid JSON response → promise resolves with the deserialized object.
     */
    public function testValidResponseResolvesWithObject(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->llmBody(self::VALID_BODY)),
        ]);
        $agent = $this->agent($mock);

        $result = $agent->structuredAsync(new UserMessage('dialog'), Summary::class)->wait();

        self::assertInstanceOf(Summary::class, $result);
        self::assertSame('Order delivery', $result->summary);
    }

    private function agent(MockHandler $mock): Agent
    {
        $stack = HandlerStack::create($mock);
        $provider = new OpenAI(
            'test-key',
            'test-model',
            [],
            false,
            new HttpClientOptions(5, 5, null, $stack),
        );
        $provider->systemPrompt('test instructions');

        $agent = new TestAgent();
        $agent->setAiProvider($provider);

        return $agent;
    }

    private function llmBody(string $content): string
    {
        return json_encode([
            'choices' => [
                [
                    'message'       => [
                        'role'    => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ]
            ],
            'usage'   => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Minimal agent without tools — structuredAsync() does not need a workflow.
 */
class TestAgent extends Agent
{
    protected function getOutputClass(): string
    {
        return Summary::class;
    }

    protected function instructions(): string
    {
        return 'test';
    }
}
