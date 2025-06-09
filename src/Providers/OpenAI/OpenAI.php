<?php

namespace NeuronAI\Providers\OpenAI;

use NeuronAI\Chat\Messages\Message;
use GuzzleHttp\Client;
use NeuronAI\HasGuzzleClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HandleWithTools;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;
use stdClass;

class OpenAI implements AIProviderInterface
{
    use HasGuzzleClient;
    use HandleWithTools;
    use HandleChat;
    use HandleStream;
    use HandleStructured;

    /**
     * The main URL of the provider API.
     *
     * @var string
     */
    protected string $baseUri = 'https://api.openai.com/v1';

    /**
     * The component responsible for mapping the NeuronAI Message to the AI provider format.
     *
     * @var MessageMapperInterface
     */
    protected MessageMapperInterface $messageMapper;

    /**
     * System instructions.
     * https://platform.openai.com/docs/api-reference/chat/create
     *
     * @var ?string
     */
    protected ?string $system = null;

    public function __construct(
        protected string $key,
        protected string $model,
        protected array  $parameters = [],
    ) {}

    public function generateToolsPayload(): array
    {
        return array_map(static function (ToolInterface $tool) {
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

            $properties = array_reduce($tool->getProperties(), static function (array $carry, ToolProperty $property) {
                $carry[$property->getName()] = [
                    'description' => $property->getDescription(),
                    'type'        => $property->getType(),
                ];

                if (!empty($property->getEnum())) {
                    $carry[$property->getName()]['enum'] = $property->getEnum();
                }

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

    public function initClient(): Client
    {
        return new Client([
            'base_uri' => trim($this->baseUri, '/') . '/',
            'headers'  => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->key,
            ],
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
        $tools = array_map(
            fn(array $item) => $this->findTool($item['function']['name'])
                ->setInputs(
                    json_decode($item['function']['arguments'], true)
                )
                ->setCallId($item['id']),
            $message['tool_calls']
        );

        $result = new ToolCallMessage(
            $message['content'] ?? '',
            $tools
        );

        return $result->addMetadata('tool_calls', $message['tool_calls']);
    }
}
