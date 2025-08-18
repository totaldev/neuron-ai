<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation\Results;

class EvaluationResult
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly int $index,
        private readonly bool $passed,
        private readonly array $input,
        private readonly mixed $output,
        private readonly float $executionTime,
        private readonly ?string $error = null
    ) {
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->input;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}