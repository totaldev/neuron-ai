<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation\Results;

class EvaluationSummary
{
    /**
     * @param array<EvaluationResult> $results
     */
    public function __construct(
        private readonly array $results,
        private readonly float $totalExecutionTime
    ) {
    }

    /**
     * @return array<EvaluationResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getTotalCount(): int
    {
        return count($this->results);
    }

    public function getPassedCount(): int
    {
        return count(array_filter($this->results, fn(EvaluationResult $result) => $result->isPassed()));
    }

    public function getFailedCount(): int
    {
        return $this->getTotalCount() - $this->getPassedCount();
    }

    public function getSuccessRate(): float
    {
        if ($this->getTotalCount() === 0) {
            return 0.0;
        }
        
        return $this->getPassedCount() / $this->getTotalCount();
    }

    public function getTotalExecutionTime(): float
    {
        return $this->totalExecutionTime;
    }

    public function getAverageExecutionTime(): float
    {
        if ($this->getTotalCount() === 0) {
            return 0.0;
        }
        
        return $this->totalExecutionTime / $this->getTotalCount();
    }

    /**
     * @return array<EvaluationResult>
     */
    public function getFailedResults(): array
    {
        return array_filter($this->results, fn(EvaluationResult $result) => !$result->isPassed());
    }

    public function hasFailures(): bool
    {
        return $this->getFailedCount() > 0;
    }
}