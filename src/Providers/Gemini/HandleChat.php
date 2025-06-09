<?php

namespace NeuronAI\Providers\Gemini;

use GuzzleHttp\Exception\GuzzleException;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\Usage;
use function array_key_exists;
use function json_decode;

trait HandleChat
{
    /**
     * Send a message to the LLM.
     *
     * @param array<Message> $messages
     * @throws GuzzleException
     */
    public function chat(array $messages): Message
    {
        $json = [
            'contents' => $this->messageMapper()->map($messages),
            ...$this->parameters,
        ];

        if (isset($this->system)) {
            $json['system_instruction'] = [
                'parts' => [
                    ['text' => $this->system],
                ],
            ];
        }

        if (!empty($this->tools)) {
            $json['tools'] = $this->generateToolsPayload();
        }

        $result = $this->getClient()->post(trim($this->baseUri, '/') . "/{$this->model}:generateContent", compact('json'))
            ->getBody()->getContents();

        $result = json_decode($result, true);

        $content = $result['candidates'][0]['content'];

        if (array_key_exists('functionCall', $content['parts'][0]) && !empty($content['parts'][0]['functionCall'])) {
            $response = $this->createToolCallMessage($content);
        } else {
            $response = new Message(MessageRole::from($content['role']), $content['parts'][0]['text'] ?? '');
        }

        // Attach the usage for the current interaction
        if (array_key_exists('usageMetadata', $result)) {
            $response->setUsage(
                new Usage(
                    $result['usageMetadata']['promptTokenCount'],
                    $result['usageMetadata']['candidatesTokenCount'] ?? $result['usageMetadata']['promptTokensDetails'][0]['tokenCount'] ?? 0
                )
            );
        }

        return $response;
    }
}
