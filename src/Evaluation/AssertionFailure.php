<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation;

class AssertionFailure
{
    public function __construct(
        private readonly string $evaluatorClass,
        private readonly string $methodName,
        private readonly string $assertionMethod,
        private readonly string $message,
        private readonly array $context = []
    ) {
    }

    public function getEvaluatorClass(): string
    {
        return $this->evaluatorClass;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getAssertionMethod(): string
    {
        return $this->assertionMethod;
    }

    public function getMessage(): string
    {
        return $this->message;
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
            '%s::%s() -> %s: %s',
            $this->getShortEvaluatorClass(),
            $this->methodName,
            $this->assertionMethod,
            $this->message
        );
    }
}
