<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation;

use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Contracts\EvaluatorInterface;

abstract class BaseEvaluator extends Assertions implements EvaluatorInterface
{
    /**
     * Get the dataset for this evaluator
     */
    abstract public function getDataset(): DatasetInterface;

    /**
     * Run the application logic being tested
     * @param array<string, mixed> $datasetItem Current item from the dataset
     * @return mixed Output from the application logic
     */
    abstract public function run(array $datasetItem): mixed;

    /**
     * Evaluate the output against expected results
     * @param mixed $output Output from the run () method
     * @param array<string, mixed> $datasetItem Reference dataset item for comparison
     * @return bool Whether the test passed
     */
    abstract public function evaluate(mixed $output, array $datasetItem): bool;
}
