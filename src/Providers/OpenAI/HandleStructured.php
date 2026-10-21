<?php

declare(strict_types=1);

namespace NeuronAI\Providers\OpenAI;

use GuzzleHttp\Promise\PromiseInterface;
use NeuronAI\Chat\Messages\Message;

use function end;
use function explode;
use function preg_match;
use function preg_replace;
use function array_replace_recursive;

trait HandleStructured
{
    public function structured(
        array $messages,
        string $class,
        array $response_format,
    ): Message {
        $tk = explode('\\', $class);
        $className = end($tk);

        $this->parameters = array_replace_recursive($this->parameters, [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'strict' => $this->strict_response,
                    "name" => $this->sanitizeClassName($className),
                    "schema" => $response_format,
                ],
            ]
        ]);

        return $this->chat($messages);
    }

    /**
     * Async variant of structured(): attaches the same response_format
     * parameters and returns the chatAsync() promise instead of blocking on chat().
     *
     * Requires the provider to implement HandleChat::chatAsync() (all providers
     * using this trait do). When a shared Guzzle client with the multiplexing
     * CurlMultiHandler is used, several structuredAsync() promises progress
     * concurrently.
     */
    public function structuredAsync(
        array $messages,
        string $class,
        array $response_format,
    ): PromiseInterface {
        $tk = explode('\\', $class);
        $className = end($tk);

        $this->parameters = array_replace_recursive($this->parameters, [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'strict' => $this->strict_response,
                    "name" => $this->sanitizeClassName($className),
                    "schema" => $response_format,
                ],
            ]
        ]);

        return $this->chatAsync($messages);
    }

    protected function sanitizeClassName(string $name): string
    {
        // Remove anonymous class markers and special characters
        $name = preg_replace('/class@anonymous.*$/', 'anonymous', $name);
        // Replace any non-alphanumeric characters with underscore
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $name);
        // Ensure it starts with a letter
        if (preg_match('/^[^a-zA-Z]/', (string) $name)) {
            return 'class_' . $name;
        }
        return $name;
    }
}
