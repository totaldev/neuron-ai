<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation;

class AssertionFailure
{
    public function __construct(
        private readonly string $evaluatorClass,
        private readonly string $assertionMethod,
        private readonly string $message,
        private readonly int $lineNumber,
        private readonly array $context = []
    ) {
    }

    public function getEvaluatorClass(): string
    {
        return $this->evaluatorClass;
    }

    public function getAssertionMethod(): string
    {
        return $this->assertionMethod;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getShortEvaluatorClass(): string
    {
        $parts = \explode('\\', $this->evaluatorClass);
        return \end($parts);
    }

    public function getFullDescription(): string
    {
        return \sprintf(
            '%s:%d -> %s: %s',
            $this->getShortEvaluatorClass(),
            $this->lineNumber,
            $this->assertionMethod,
            $this->message
        );
    }
}
