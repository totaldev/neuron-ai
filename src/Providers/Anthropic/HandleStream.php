<?php

namespace NeuronAI\Providers\Anthropic;

use Generator;
use GuzzleHttp\Exception\GuzzleException;
use NeuronAI\Exceptions\ProviderException;
use Psr\Http\Message\StreamInterface;
use Throwable;
use function array_key_exists;
use function array_map;
use function compact;
use function end;
use function json_decode;
use function json_encode;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

trait HandleStream
{
    /**
     * Stream response from the LLM.
     *
     * @throws ProviderException
     * @throws GuzzleException
     */
    public function stream(array|string $messages, callable $executeToolsCallback): Generator
    {
        $json = [
            'stream'     => true,
            'model'      => $this->model,
            'max_tokens' => $this->max_tokens,
            'system'     => $this->system ?? null,
            'messages'   => $this->messageMapper()->map($messages),
            ...$this->parameters,
        ];

        if (!empty($this->tools)) {
            $json['tools'] = $this->generateToolsPayload();
        }

        // https://docs.anthropic.com/claude/reference/messages_post
        $stream = $this->getClient()->post('messages', [
            'stream' => true,
            ...compact('json'),
        ])->getBody();

        $toolCalls = [];

        while (!$stream->eof()) {
            if (!$line = $this->parseNextDataLine($stream)) {
                continue;
            }

            // https://docs.anthropic.com/en/api/messages-streaming
            if ($line['type'] === 'message_start') {
                yield json_encode(['usage' => $line['message']['usage']]);
                continue;
            }

            if ($line['type'] === 'message_delta') {
                yield json_encode(['usage' => $line['usage']]);
                continue;
            }

            // Tool calls detection (https://docs.anthropic.com/en/api/messages-streaming#streaming-request-with-tool-use)
            if (
                (isset($line['content_block']['type']) && $line['content_block']['type'] === 'tool_use') ||
                (isset($line['delta']['type']) && $line['delta']['type'] === 'input_json_delta')
            ) {
                $toolCalls = $this->composeToolCalls($line, $toolCalls);
                continue;
            }

            // Handle tool call
            if ($line['type'] === 'content_block_stop' && !empty($toolCalls)) {
                // Restore the input field as an array
                $toolCalls = array_map(function (array $call) {
                    $call['input'] = json_decode($call['input'], true);

                    return $call;
                }, $toolCalls);

                yield from $executeToolsCallback(
                    $this->createToolCallMessage(end($toolCalls))
                );
            }

            // Process regular content
            $content = $line['delta']['text'] ?? '';

            yield $content;
        }
    }

    /**
     * Recreate the tool_call format of anthropic API from streaming.
     *
     * @param array<string, mixed>             $line
     * @param array<int, array<string, mixed>> $toolCalls
     * @return array<int, array<string, mixed>>
     */
    protected function composeToolCalls(array $line, array $toolCalls): array
    {
        if (!array_key_exists($line['index'], $toolCalls)) {
            $toolCalls[$line['index']] = [
                'type'  => 'tool_use',
                'id'    => $line['content_block']['id'],
                'name'  => $line['content_block']['name'],
                'input' => '',
            ];
        } else {
            if ($input = $line['delta']['partial_json'] ?? null) {
                $toolCalls[$line['index']]['input'] .= $input;
            }
        }

        return $toolCalls;
    }

    protected function parseNextDataLine(StreamInterface $stream): ?array
    {
        $line = $this->readLine($stream);

        if (!str_starts_with($line, 'data:')) {
            return null;
        }

        $line = trim(substr($line, strlen('data: ')));

        try {
            return json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new ProviderException('Anthropic streaming error - ' . $exception->getMessage());
        }
    }

    protected function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            $byte = $stream->read(1);

            if ($byte === '') {
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
