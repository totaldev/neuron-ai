<?php

namespace NeuronAI\Providers\Gemini;

use GuzzleHttp\Client;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\HasGuzzleClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HandleWithTools;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;
use stdClass;
use function array_filter;
use function array_map;
use function array_reduce;

class Gemini implements AIProviderInterface
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
    protected string $baseUri = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * The component responsible for mapping the NeuronAI Message to the AI provider format.
     *
     * @var MessageMapperInterface
     */
    protected MessageMapperInterface $messageMapper;

    /**
     * System instructions.
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
        $tools = array_map(function (ToolInterface $tool) {
            $payload = [
                'name'        => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new stdClass(),
                    'required'   => [],
                ],
            ];

            $properties = array_reduce($tool->getProperties(), function (array $carry, ToolProperty $property) {
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
                $payload['parameters'] = [
                    'type'       => 'object',
                    'properties' => $properties,
                    'required'   => $tool->getRequiredProperties(),
                ];
            }

            return $payload;
        }, $this->tools);

        return [
            'functionDeclarations' => $tools,
        ];
    }

    public function initClient(): Client
    {
        return new Client([
            // Since Gemini use colon ":" into the URL guxxle fire an exception udsing base_uri configuration.
            //'base_uri' => trim($this->baseUri, '/').'/',
            'headers' => [
                'Accept'         => 'application/json',
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $this->key,
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
        $tools = array_map(function (array $item) {
            if (!isset($item['functionCall'])) {
                return null;
            }

            // Gemini does not use ID. It uses the tool's name as a unique identifier.
            return $this->findTool($item['functionCall']['name'])
                ->setInputs($item['functionCall']['args'])
                ->setCallId($item['functionCall']['name']);
        }, $message['parts']);

        $result = new ToolCallMessage(
            $message['content'] ?? null,
            array_filter($tools)
        );
        $result->setRole(MessageRole::MODEL);

        return $result;
    }
}
