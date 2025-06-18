<?php

namespace NeuronAI\Providers\Ollama;

use GuzzleHttp\Client;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\HasGuzzleClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HandleWithTools;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolPropertyInterface;
use stdClass;

class Ollama implements AIProviderInterface
{
    use HasGuzzleClient;
    use HandleWithTools;
    use HandleChat;
    use HandleStream;
    use HandleStructured;

    /**
     * The component responsible for mapping the NeuronAI Message to the AI provider format.
     *
     * @var MessageMapperInterface
     */
    protected MessageMapperInterface $messageMapper;

    protected ?string $system = null;

    public function __construct(
        protected string $url, // http://localhost:11434/api
        protected string $model,
        protected array  $parameters = [],
    ) {}

    public function initClient(): Client
    {
        return new Client([
            'base_uri' => trim($this->url, '/') . '/',
        ]);
    }

    public function messageMapper(): MessageMapperInterface
    {
        if (!isset($this->messageMapper)) {
            $this->messageMapper = new MessageMapper();
        }

        return $this->messageMapper;
    }

    public function systemPrompt(?string $prompt): AIProviderInterface
    {
        $this->system = $prompt;

        return $this;
    }

    protected function createToolCallMessage(array $message): Message
    {
        $tools = array_map(fn(array $item) => $this->findTool($item['function']['name'])
            ->setInputs($item['function']['arguments']), $message['tool_calls']);

        $result = new ToolCallMessage(
            $message['content'],
            $tools
        );

        return $result->addMetadata('tool_calls', $message['tool_calls']);
    }

    protected function generateToolsPayload(): array
    {
        return array_map(function (ToolInterface $tool) {
            $payload = [
                'type'     => 'function',
                'function' => [
                    'name'        => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new stdClass(),
                        'required'   => [],
                    ],
                ],
            ];

            $properties = array_reduce($tool->getProperties(), function (array $carry, ToolPropertyInterface $property) {
                $carry[$property->getName()] = [
                    'type'        => $property->getType()->value,
                    'description' => $property->getDescription(),
                ];

                return $carry;
            }, []);

            if (!empty($properties)) {
                $payload['function']['parameters'] = [
                    'type'       => 'object',
                    'properties' => $properties,
                    'required'   => $tool->getRequiredProperties(),
                ];
            }

            return $payload;
        }, $this->tools);
    }
}
