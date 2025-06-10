<?php

namespace NeuronAI\RAG;

use NeuronAI\Chat\Messages\Message;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Observability\Events\PostProcessed;
use NeuronAI\Observability\Events\PostProcessing;
use NeuronAI\RAG\PostProcessor\PostProcessorInterface;

trait VectorSearchTrait
{
    use ResolveVectorStore;
    use ResolveEmbeddingProvider;

    /**
     * @var array<PostprocessorInterface>
     */
    protected array $postProcessors = [];

    /**
     * Feed the vector store with documents.
     *
     * @param array<Document> $documents
     * @return void
     */
    public function addDocuments(array $documents): void
    {
        $this->resolveVectorStore()->addDocuments(
            $this->resolveEmbeddingsProvider()->embedDocuments($documents)
        );
    }

    /**
     * @throws AgentException
     */
    public function setPostProcessors(array $postProcessors): RAG
    {
        foreach ($postProcessors as $processor) {
            if (!$processor instanceof PostProcessorInterface) {
                throw new AgentException($processor::class . " must implement PostProcessorInterface");
            }

            $this->postProcessors[] = $processor;
        }

        return $this;
    }

    /**
     * Apply a series of postprocessors to the retrieved documents.
     *
     * @param Message         $question  The question to process the documents for.
     * @param array<Document> $documents The documents to process.
     * @return array<Document> The processed documents.
     */
    protected function applyPostProcessors(Message $question, array $documents): array
    {
        foreach ($this->postProcessors() as $processor) {
            $this->notify('rag-postprocessing', new PostProcessing($processor::class, $question, $documents));
            $documents = $processor->process($question, $documents);
            $this->notify('rag-postprocessed', new PostProcessed($processor::class, $question, $documents));
        }

        return $documents;
    }

    /**
     * @return PostProcessorInterface[]
     */
    protected function postProcessors(): array
    {
        return $this->postProcessors;
    }

    /**
     * Retrieve relevant documents from the vector store.
     *
     * @return array<Document>
     */
    private function searchDocuments(string $question): array
    {
        $docs = $this->resolveVectorStore()->similaritySearch(
            $this->resolveEmbeddingsProvider()->embedText($question)
        );

        $retrievedDocs = [];

        foreach ($docs as $doc) {
            //md5 for removing duplicates
            $retrievedDocs[md5($doc->content)] = $doc;
        }

        return array_values($retrievedDocs);
    }
}
