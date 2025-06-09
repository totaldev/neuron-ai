<?php

namespace NeuronAI\Providers\Ollama;

use Generator;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use Psr\Http\Message\StreamInterface;
use function array_unshift;
use function compact;
use function json_decode;
use function json_encode;

trait HandleStream
{
    public function stream(array|string $messages, callable $executeToolsCallback): Generator
    {
        // Include the system prompt
        if (isset($this->system)) {
            array_unshift($messages, new Message(MessageRole::SYSTEM, $this->system));
        }

        $json = [
            'stream'   => true,
            'model'    => $this->model,
            'messages' => $this->messageMapper()->map($messages),
            ...$this->parameters,
        ];

        if (!empty($this->tools)) {
            $json['tools'] = $this->generateToolsPayload();
        }

        $stream = $this->getClient()->post('chat', [
            'stream' => true,
            ...compact('json'),
        ])->getBody();

        while (!$stream->eof()) {
            if (!$line = $this->parseNextJson($stream)) {
                continue;
            }

            // Last chunk will contains the usage information.
            if ($line['done'] === true) {
                yield json_encode(['usage' => [
                    'input_tokens'  => $line['prompt_eval_count'],
                    'output_tokens' => $line['eval_count'],
                ]]);
                continue;
            }

            // Process tool calls
            // Ollama doesn't support tool calls for stream response
            // https://github.com/ollama/ollama/blob/main/docs/api.md

            // Process regular content
            $content = $line['message']['content'] ?? '';

            yield $content;
        }
    }

    protected function parseNextJson(StreamInterface $stream): ?array
    {
        $line = $this->readLine($stream);

        if (empty($line)) {
            return null;
        }

        $json = json_decode($line, true);

        if ($json['done']) {
            return null;
        }

        if (!isset($json['message']) || $json['message']['role'] !== 'assistant') {
            return null;
        }

        return $json;
    }

    protected function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }
}
