<?php

namespace NeuronAI\RAG;

use Generator;
use NeuronAI\Agent;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Observability\Events\InstructionsChanged;
use NeuronAI\Observability\Events\InstructionsChanging;
use NeuronAI\Exceptions\MissingCallbackParameter;
use NeuronAI\Exceptions\ToolCallableNotSet;
use NeuronAI\Observability\Events\VectorStoreResult;
use NeuronAI\Observability\Events\VectorStoreSearching;
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
     * Retrieve relevant documents from the vector store.
     *
     * @return Document[]
     */
    public function retrieveDocuments(Message $question): array
    {
        $this->notify('rag-vectorstore-searching', new VectorStoreSearching($question));

        $documents = $this->resolveVectorStore()->similaritySearch(
            $this->resolveEmbeddingsProvider()->embedText($question->getContent()),
        );

        $retrievedDocs = [];

        foreach ($documents as $document) {
            //md5 for removing duplicates
            $retrievedDocs[md5($document->getContent())] = $document;
        }

        $retrievedDocs = array_values($retrievedDocs);

        $this->notify('rag-vectorstore-result', new VectorStoreResult($question, $retrievedDocs));

        return $this->applyPostProcessors($question, $retrievedDocs);
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

    /**
     * Set the system message based on the context.
     *
     * @param Document[] $documents
     */
    public function withDocumentsContext(array $documents): AgentInterface
    {
        $originalInstructions = $this->instructions();
        $this->notify('rag-instructions-changing', new InstructionsChanging($originalInstructions));

        // Remove the old context to avoid infinite grow
        $newInstructions = $this->removeDelimitedContent($originalInstructions, '<EXTRA-CONTEXT>', '</EXTRA-CONTEXT>');

        $newInstructions .= '<EXTRA-CONTEXT>';
        foreach ($documents as $document) {
            $newInstructions .= $document->getContent() . PHP_EOL . PHP_EOL;
        }
        $newInstructions .= '</EXTRA-CONTEXT>';

        $this->withInstructions(trim($newInstructions));
        $this->notify('rag-instructions-changed', new InstructionsChanged($originalInstructions, $this->instructions()));

        return $this;
    }

    protected function retrieval(Message $question): void
    {
        $this->withDocumentsContext(
            $this->retrieveDocuments($question)
        );
    }

    /**
     * @deprecated Use withDocumentsContext instead.
     */
    protected function setSystemMessage(array $documents): AgentInterface
    {
        return $this->withDocumentsContext($documents);
    }
}
