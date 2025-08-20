---
description: Interact with LLM providers or extend the framework to implement new ones.
---

# AI provider

With Neuron you can switch between LLM providers with just one line of code, without any impact on your agent implementation.

### Anthropic

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;

class MyAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: 'ANTHROPIC_API_KEY',
            model: 'ANTHROPIC_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### OpenAI

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: 'OPENAI_API_KEY',
            model: 'OPENAI_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### AzureOpenAI

This provider allows you to connect with OpenAI models provided in the Azure cloud platform.

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\AzureOpenAI;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new AzureOpenAI(
            key: 'AZURE_API_KEY',
            endpoint: 'AZURE_ENDPOINT',
            model: 'OPENAI_MODEL',
            version: 'AZURE_API_VERSION'
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### Ollama

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Ollama(
            url: 'OLLAMA_URL',
            model: 'OLLAMA_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### Gemini

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Gemini(
            key: 'GEMINI_API_KEY',
            model: 'GEMINI_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### Mistral

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Mistral\Mistral;

class MyAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Mistral(
            key: 'MISTRAL_API_KEY',
            model: 'MISTRAL_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### HuggingFace

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HuggingFace\HuggingFace;
use NeuronAI\Providers\HuggingFace\InferenceProvider;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new HuggingFace(
            key: 'HF_ACCESS_TOKEN',
            model: 'mistralai/Mistral-7B-Instruct-v0.3',
            // https://huggingface.co/docs/inference-providers/en/index
            inferenceProvider: InferenceProvider::HF_INFERENCE,
            parameters: [
                'max_tokens' => 500,
                'temperature' => 0.5
            ]
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### Deepseek

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Deepseek\Deepseek;

class MyAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Deepseek(
            key: 'DEEPSEEK_API_KEY',
            model: 'DEEPSEEK_MODEL',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

{% hint style="danger" %}
Due to the Deepseek API limitations, it doesn't support document and image attachments.
{% endhint %}

### Grok (X-AI)

```php
namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\XAI\Grok;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Grok(
            key: 'GROK_API_KEY',
            model: 'grok-4',
        );
    }
}

echo MyAgent::make()->chat(new UserMessage("Hi!"));
// Hi, how can I help you today?
```

### Custom Http Options

Providers use an HTTP client to communicate with the remote service. You can customize the configuration of the HTTP client passing an instance of `\NeuronAI\Providers\HttpClientOptions`:

```php
use NeuronAI\Providers\HttpClientOptions;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Ollama(
            url: 'OLLAMA_URL',
            model: 'OLLAMA_MODEL',
            httpOptions: new HttpClientOptions(timeout: 30)
        );
    }
}
```

`HttpClientOptions` class allows customization of `timeout`, `connect_timeout`, and `headers`.

## Implement a custom provider

If you want to create a new provider you have to implement the `AIProviderInterface` interface:

```php
namespace NeuronAI\Providers;

use NeuronAI\Chat\Messages\Message;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Providers\MessageMapperInterface;

interface AIProviderInterface
{
    /**
     * Send predefined instruction to the LLM.
     */
    public function systemPrompt(?string $prompt): AIProviderInterface;

    /**
     * Set the tools to be exposed to the LLM.
     *
     * @param array<ToolInterface> $tools
     */
    public function setTools(array $tools): AIProviderInterface;
    
    /**
     * The component responsible for mapping the NeuronAI Message to the AI provider format.
     */
    public function messageMapper(): MessageMapperInterface;

    /**
     * Send a prompt to the AI agent.
     */
    public function chat(array $messages): Message;
    
    /**
     * Yield the LLM response.
     */
    public function stream(array|string $messages, callable $executeToolsCallback): \Generator;
    
    /**
     * Schema validated response.
     */
    public function structured(string $class, Message|array $messages, int $maxRetry = 1): mixed;
}
```

The `chat` method should contains the call the underlying LLM. If the provider doesn't support tools and function calls, you can implement it with a placeholder.

This is the basic template for a new AI provider implementation.

```php
namespace App\Neuron\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HandleWithTools;
use NeuronAI\Providers\MessageMapperInterface;

class MyAIProvider implements AIProviderInterface
{
    use HandleWithTools;
    
    /**
     * The http client.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * System instructions.
     *
     * @var string
     */
    protected string $system;

    /**
     * The component responsible for mapping the NeuronAI Message to the AI provider format.
     *
     * @var MessageMapperInterface
     */
    protected MessageMapperInterface $messageMapper;
    
    public function __construct(
        protected string $key,
        protected string $model
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.provider.com/v1',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->key}",
            ]
        ]);
    }

    /**
     * @inerhitDoc
     */
    public function systemPrompt(string $prompt): AIProviderInterface
    {
        $this->system = $prompt;
        return $this;
    }

    public function messageMapper(): MessageMapperInterface
    {
        if (!isset($this->messageMapper)) {
            $this->messageMapper = new MessageMapper();
        }
        return $this->messageMapper;
    }

    /**
     * @inerhitDoc
     */
    public function chat(array $messages): Message
    {
        $result = $this->client->post('chat', [
            RequestOptions::JSON => [
                'model' => $this->model,
                'messages' => \array_map(function (Message $message) {
                    return $message->jsonSerialize();
                }, $messages)
            ]
        ])->getBody()->getContents();
        
        $result = \json_decode($result, true);

        return new AssistantMessage($result['content']);
    }
}
```

After creating your own implementation you can use it in the agent:

```php
namespace App\Neuron;

use App\Neuron\Providers\MyAIProvider;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new MyAIProvider (
            key: 'PROVIDER_API_KEY',
            model: 'PROVIDER_MODEL',
        );
    }
}
```

{% hint style="warning" %}
We strongly recommend you to submit new provider implementations via PR on the official repository or using other [Inspector.dev](https://inspector.dev/developer-support/) support channels. The new implementation can receives an important boost in its advancement by the community.
{% endhint %}

### OpenAI compatible providers

If you want to interact to an LLM provider that support the same API format of OpenAI you can easily create e dedicated class in a few lines of code, just extending our OpenAI provider:

```php
namespace App\Neuron\Providers;

use NeuronAI\Providers\OpenAI\OpenAI;

class OpenRouter extends OpenAI
{
    protected string $baseUri = "https://openrouter.ai/api/v1";
}
```

That's it.

You can now use the new provider into your Agent:

```php
use App\Neuron\Providers\TogetherAI;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;

class MyAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new OpenRouter(
            key: 'OPENROUTER_API_KEY',
            model: 'OPENROUTER_MODEL',
            parameters: [
                'models' => [...]
            ]
        );
    }
}
```
