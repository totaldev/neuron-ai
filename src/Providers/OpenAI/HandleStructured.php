<?php

declare(strict_types=1);

namespace NeuronAI\Providers\OpenAI;

use NeuronAI\Chat\Messages\Message;

trait HandleStructured
{
    public function structured(
        array $messages,
        string $class,
        array $response_format,
        bool $strict = false,
    ): Message {
        $tk = \explode('\\', $class);
        $className = \end($tk);

        $this->parameters = \array_merge($this->parameters, [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'strict' => $strict,
                    "name" => $this->sanitizeClassName($className),
                    "schema" => $response_format,
                ],
            ]
        ]);

        return $this->chat($messages);
    }

    protected function sanitizeClassName(string $name): string
    {
        // Remove anonymous class markers and special characters
        $name = \preg_replace('/class@anonymous.*$/', 'anonymous', $name);
        // Replace any non-alphanumeric characters with underscore
        $name = \preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $name);
        // Ensure it starts with a letter
        if (\preg_match('/^[^a-zA-Z]/', (string) $name)) {
            return 'class_' . $name;
        }
        return $name;
    }
}
