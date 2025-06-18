<?php

namespace NeuronAI\RAG\VectorStore;

use Generator;
use NeuronAI\Exceptions\VectorStoreException;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\Search\SimilaritySearch;
use function array_map;
use function array_slice;
use function count;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function usort;

class FileVectorStore implements VectorStoreInterface
{
    /**
     * @throws VectorStoreException
     */
    public function __construct(
        protected string $directory,
        protected int    $topK = 4,
        protected string $name = 'neuron',
        protected string $ext = '.store'
    ) {
        if (!is_dir($this->directory)) {
            throw new VectorStoreException("Directory '{$this->directory}' does not exist");
        }
    }

    public function addDocument(Document $document): void
    {
        $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents): void
    {
        $this->appendToFile(
            array_map(static fn(Document $document) => $document->jsonSerialize(), $documents)
        );
    }

    public function similaritySearch(array $embedding): array
    {
        $topItems = [];

        foreach ($this->getLine($this->getFilePath()) as $document) {
            $document = json_decode($document, true);

            if (empty($document['embedding'])) {
                throw new VectorStoreException("Document with the following content has no embedding: {$document['content']}");
            }
            $dist = $this->cosineSimilarity($embedding, $document['embedding']);

            $topItems[] = compact('dist', 'document');

            usort($topItems, fn($a, $b) => $a['dist'] <=> $b['dist']);

            if (count($topItems) > $this->topK) {
                $topItems = array_slice($topItems, 0, $this->topK, true);
            }
        }

        return array_map(static function ($item) {
            $itemDoc = $item['document'];
            $document = new Document($itemDoc['content']);
            $document->embedding = $itemDoc['embedding'];
            $document->sourceType = $itemDoc['sourceType'];
            $document->sourceName = $itemDoc['sourceName'];
            $document->id = $itemDoc['id'];
            $document->score = 1 - $item['dist'];
            $document->metadata = $itemDoc['metadata'] ?? [];

            return $document;
        }, $topItems);
    }

    protected function appendToFile(array $documents): void
    {
        file_put_contents(
            $this->getFilePath(),
            implode(PHP_EOL, array_map(fn(array $vector) => json_encode($vector), $documents)) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * @throws VectorStoreException
     */
    protected function cosineSimilarity(array $vector1, array $vector2): float
    {
        return SimilaritySearch::cosine($vector1, $vector2);
    }

    protected function getFilePath(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $this->name . $this->ext;
    }

    protected function getLine($file): Generator
    {
        $f = fopen($file, 'rb');

        try {
            while ($line = fgets($f)) {
                yield $line;
            }
        } finally {
            fclose($f);
        }
    }
}
