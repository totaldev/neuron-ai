<?php

namespace NeuronAI\RAG;

use Generator;
use NeuronAI\Agent;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Observability\Events\InstructionsChanged;
use NeuronAI\Observability\Events\InstructionsChanging;
use NeuronAI\Observability\Events\VectorStoreResult;
use NeuronAI\Observability\Events\VectorStoreSearching;
use NeuronAI\Exceptions\MissingCallbackParameter;
use NeuronAI\Exceptions\ToolCallableNotSet;
use NeuronAI\Providers\AIProviderInterface;
use Throwable;

/**
 * @method RAG withProvider(AIProviderInterface $provider)
 */
class RAG extends Agent
{
    use VectorSearchTrait;

    /**
     * @throws MissingCallbackParameter
     * @throws ToolCallableNotSet
     * @throws Throwable
     */
    public function answer(Message $question): Message
    {
        $this->notify('rag-start');

        $this->retrieval($question);

        $response = $this->chat($question);

        $this->notify('rag-stop');

        return $response;
    }

    /**
     * @throws AgentException
     */
    public function streamAnswer(Message $question): Generator
    {
        $this->notify('rag-start');

        $this->retrieval($question);

        yield from $this->stream($question);

        $this->notify('rag-stop');
    }

    protected function retrieval(Message $question): void
    {
        $this->notify('rag-vectorstore-searching', new VectorStoreSearching($question));
        $documents = $this->searchDocuments($question->getContent());
        $this->notify('rag-vectorstore-result', new VectorStoreResult($question, $documents));

        $documents = $this->applyPostProcessors($question, $documents);

        $originalInstructions = $this->instructions();
        $this->notify('rag-instructions-changing', new InstructionsChanging($originalInstructions));
        $this->setSystemMessage($documents);
        $this->notify('rag-instructions-changed', new InstructionsChanged($originalInstructions, $this->instructions()));
    }

    /**
     * Set the system message based on the context.
     *
     * @param array<Document> $documents
     * @return AgentInterface
     */
    protected function setSystemMessage(array $documents): AgentInterface
    {
        $context = '';
        foreach ($documents as $document) {
            $context .= $document->content . ' ';
        }

        return $this->withInstructions(
            $this->instructions() . PHP_EOL . PHP_EOL . "# EXTRA INFORMATION AND CONTEXT" . PHP_EOL . $context
        );
    }
}
