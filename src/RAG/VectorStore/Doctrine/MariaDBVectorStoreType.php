<?php

declare(strict_types=1);

namespace NeuronAI\RAG\VectorStore\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

class MariaDBVectorStoreType extends SupportedDoctrineVectorStore
{
    public function getVectorAsString(array $vector): string
    {
        return '['.$this->stringListOf($vector).']';
    }

    public function convertToDatabaseValueSQL(string $sqlExpression): string
    {
        return \sprintf('Vec_FromText(%s)', $sqlExpression);
    }

    public function addCustomisationsTo(EntityManagerInterface $entityManager): void
    {
        $entityManager->getConfiguration()->addCustomStringFunction($this->l2DistanceName(), MariaDBVectorL2OperatorDql::class);
    }

    public function l2DistanceName(): string
    {
        return 'VEC_DISTANCE_EUCLIDEAN';
    }
}
