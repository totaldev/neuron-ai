# FORK: Create Full-Featured AI Agents As Standalone Components In Any PHP Application

[![Latest Stable Version](https://poser.pugx.org/inspector-apm/neuron-ai/v/stable)](https://packagist.org/packages/inspector-apm/neuron-ai)
[![Total Downloads](http://poser.pugx.org/inspector-apm/neuron-ai/downloads)](https://packagist.org/packages/inspector-apm/neuron-ai)

> Before moving on, support the community giving a GitHub star ⭐️. Thank you!

[**Video Tutorial**](https://www.youtube.com/watch?v=oSA1bP_j41w)

[![Neuron & Inspector](./docs/images/youtube.png)](https://www.youtube.com/watch?v=oSA1bP_j41w)

---

## Requirements

- PHP: ^8.1

## Official documentation

**[Go to the official documentation](https://neuron.inspector.dev/)**

## Guides & Tutorials

Check out the technical guides and tutorials archive to learn how to start creating your AI Agents with Neuron
https://docs.neuron-ai.dev/resources/guides-and-tutorials.

## Neuron AI Examples

- [Install](#install)
- [Create an Agent](#create)
- [Talk to the Agent](#talk)
- [Monitoring](#monitoring)
- [Supported LLM Providers](#providers)
- [Tools & Function Calls](#tools)
- [MCP server connector](#mcp)
- [Structured Output](#structured)
- [RAG](#rag)
- [Workflow](#workflow)
- [Official Documentation](#documentation)

<a name="install">

## Install

Install the latest version of the package:

```
composer require inspector-apm/neuron-ai
```

<a name="create">

## Create an Agent

Neuron provides you with the Agent class you can extend to inherit the main features of the framework
and create fully functional agents. This class automatically manages some advanced mechanisms for you, such as memory,
tools and function calls, up to the RAG systems. You can go deeper into these aspects in the [documentation](https://docs.neuron-ai.dev).
In the meantime, let's create the first agent, extending the `NeuronAI\Agent` class:

```php
<?php

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;

class DataAnalystAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: 'ANTHROPIC_API_KEY',
            model: 'ANTHROPIC_MODEL',
        );
    }

    public function instructions(): string
    {
        return new SystemPrompt(
            background: [
                "You are a data analyst expert in creating reports from SQL databases."
            ]
        );
    }
}
```

The `SystemPrompt` class is designed to take your base instructions and build a consistent prompt for the underlying model
reducing the effort for prompt engineering.

<a name="talk">

## Talk to the Agent

Send a prompt to the agent to get a response from the underlying LLM:

```php

$agent = DataAnalystAgent::make();


$response = $agent->chat(
    new UserMessage("Hi, I'm Valerio. Who are you?")
);
echo $response->getContent();
// I'm a data analyst. How can I help you today?


$response = $agent->chat(
    new UserMessage("Do you remember my name?")
);
echo $response->getContent();
// Your name is Valerio, as you said in your introduction.
```

As you can see in the example above, the Agent automatically has memory of the ongoing conversation. Learn more about memory in the [documentation](https://docs.neuron-ai.dev/chat-history-and-memory).

<a name="monitoring">

## Monitoring

Integrating AI Agents into your application you’re not working only with functions and deterministic code,
you program your agent also influencing probability distributions. Same input ≠ output.
That means reproducibility, versioning, and debugging become real problems.

Many of the Agents you build with NeuronAI will contain multiple steps with multiple invocations of LLM calls,
tool usage, access to external memories, etc. As these applications get more and more complex, it becomes crucial
to be able to inspect what exactly your agent is doing and why.

Why is the model taking certain decisions? What data is the model reacting to? Prompting is not programming
in the common sense. No static types, small changes break output, long prompts cost latency,
and no two models behave exactly the same with the same prompt.

The best way to do this is with [Inspector](https://inspector.dev). After you sign up,
make sure to set the `INSPECTOR_INGESTION_KEY` variable in the application environment file to start monitoring:

```dotenv
INSPECTOR_INGESTION_KEY=fwe45gtxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

After configuring the environment variable, you will see the agent execution timeline in your Inspector dashboard.

![](./docs/images/neuron-observability.avif)

Learn more about Monitoring in the [documentation](https://docs.neuron-ai.dev/advanced/observability).

<a name="providers">

## Supported LLM Providers

With NeuronAI, you can switch between [LLM providers](https://docs.neuron-ai.dev/components/ai-provider) with just one line of code, without any impact on your agent implementation.
Supported providers:

- Anthropic
- Ollama (also as an [embeddings provider](https://docs.neuron-ai.dev/components/embeddings-provider#ollama))
- OpenAI (also as an [embeddings provider](https://docs.neuron-ai.dev/components/embeddings-provider#openai))
- OpenAI on Azure
- Gemini (also as an [embeddings provider](https://docs.neuron-ai.dev/components/embeddings-provider#gemini))
- HuggingFace
- Mistral
- Grok

<a name="tools">

## Tools & Toolkits

Make your agent able to perform concrete tasks, like reading from a database, by adding tools or toolkits (collections of tools).

```php
<?php

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLToolkit;

class DataAnalystAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: 'ANTHROPIC_API_KEY',
            model: 'ANTHROPIC_MODEL',
        );
    }

    public function instructions(): string
    {
        return new SystemPrompt(
            background: [
                "You are a data analyst expert in creating reports from SQL databases."
            ]
        );
    }

    public function tools(): array
    {
        return [
            MySQLToolkit:make(
                \DB::connection()->getPdo()
            ),
        ];
    }
}
```

Learn more about Tools in the [documentation](https://docs.neuron-ai.dev/tools-and-function-calls).

<a name="mcp">

## MCP server connector

Instead of implementing tools manually, you can connect tools exposed by an MCP server with the `McpConnector` component:

```php
<?php

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\Tool;

class DataAnalystAgent extends Agent
{
    public function provider(): AIProviderInterface
    {
        ...
    }

    public function instructions(): string
    {
        ...
    }

    public function tools(): array
    {
        return [
            // Connect to an MCP server
            ...McpConnector::make([
                'command' => 'npx',
                'args' => ['-y', '@modelcontextprotocol/server-everything'],
            ])->tools(),
        ];
    }
}
```

Learn more about MCP connector in the [documentation](https://docs.neuron-ai.dev/advanced/mcp-servers-connection).

<a name="structured">

## Structured Output
For many applications, such as chatbots, Agents need to respond to users directly in natural language.
However, there are scenarios where we need Agents to understand natural language, but output in a structured format.

One common use-case is extracting data from text to insert into a database or use with some other downstream system.
This guide covers a few strategies for getting structured outputs from the agent.

```php
use App\Neuron\MyAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\StructuredOutput\SchemaProperty;

/*
 * Define the output structure as a PHP class.
 */
class Person
{
    #[SchemaProperty(description: 'The user name')]
    public string $name;

    #[SchemaProperty(description: 'What the user love to eat')]
    public string $preference;
}

// Talk to the agent requiring the structured output
$person = MyAgent::make()->structured(
    new UserMessage("I'm John and I like pizza!"),
    Person::class
);

echo $person->name ' like '.$person->preference;
// John like pizza
```

Learn more about Structured Output on the [documentation](https://docs.neuron-ai.dev/advanced/structured-output).

<a name="rag">

## RAG

For RAG use case, you must extend the `NeuronAI\RAG\RAG` class instead of the default Agent class.

To create a RAG you need to attach some additional components other than the AI provider, such as a `vector store`,
and an `embeddings provider`.

Here is an example of a RAG implementation:

```php
<?php

namespace App\Neuron;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\VoyageEmbeddingProvider;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\PineconeVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

class MyChatBot extends RAG
{
    public function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: 'ANTHROPIC_API_KEY',
            model: 'ANTHROPIC_MODEL',
        );
    }

    public function embeddings(): EmbeddingsProviderInterface
    {
        return new VoyageEmbeddingProvider(
            key: 'VOYAGE_API_KEY',
            model: 'VOYAGE_MODEL'
        );
    }

    public function vectorStore(): VectorStoreInterface
    {
        return new PineconeVectorStore(
            key: 'PINECONE_API_KEY',
            indexUrl: 'PINECONE_INDEX_URL'
        );
    }
}
```

Learn more about RAG in the [documentation](https://docs.neuron-ai.dev/rag).

<a name="workflow">

## Workflow
Think of a Workflow as a smart flowchart for your AI applications. The idea behind Workflow is to allow developers
to use all the NeuronAI components like AI providers, embeddings, data loaders, chat history, vector store, etc,
as standalone components to create totally customized agentic entities.

Agent and RAG classes represent a ready to use implementation of the most common patterns when it comes
to retrieval use cases, or tool calls, structured output, etc. Workflow allows you to program your
agentic system completely from scratch. Agent and RAG can be used inside a Workflow to complete tasks
as any other component if you need their built-in capabilities.

As an illustrative example, let's consider a simple workflow with two nodes.
The connection (Edge) tells the workflow to go from A to B to C.

```php
<?php

namespace App\Neuron\Workflow;

use App\Neuron\Workflow\InitialNode;
use App\Neuron\Workflow\MiddleNode;
use App\Neuron\Workflow\FinishNode;
use NeuronAI\Workflow\Edge;
use NeuronAI\Workflow\Workflow;

class SimpleWorkflow extends Workflow
{
    public function nodes(): array
    {
        return [
            new InitialNode(),
            new MiddleNode(),
            new FinishNode(),
        ];
    }

    public function edges(): array
    {
        return [
            // Tell the workflow to go to MiddleNode after InitialNode
            new Edge(InitialNode::class, MiddleNode::class),

            // Tell the workflow to go to FinishNode after MiddleNode
            new Edge(MiddleNode::class, FinishNode::class),
        ];
    }

    protected function start(): string
    {
        return InitialNode::class;
    }

    protected function end(): array
    {
        return [
            FinishNode::class,
        ];
    }
}
```

[![NeuronAI Workflow](./docs/images/workflow.avif)](https://docs.neuron-ai.dev/workflow/getting-started)

Neuron Workflow supports a robust [**human-in-the-loop**](https://docs.neuron-ai.dev/workflow/human-in-the-loop)
pattern, enabling human intervention at any point in an automated process. This is especially useful in
large language model (LLM)-driven applications where model output may require validation, correction,
or additional context to complete the task.

Learn more about Structured Output on the [documentation](https://docs.neuron-ai.dev/workflow/getting-started).

<a name="documentation">

## Official documentation

**[Go to the official documentation](https://neuron.inspector.dev/)**


