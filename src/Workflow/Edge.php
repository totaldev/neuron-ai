<?php

namespace NeuronAI\Workflow;

use ReflectionClass;

class Edge
{
    private string $from;
    private string $to;

    public function __construct(
        string $fromClass,
        string $toClass,
        private ?\Closure $condition = null
    ) {
        $this->from = $this->getShortClassName($fromClass);
        $this->to = $this->getShortClassName($toClass);
    }

    private function getShortClassName(string $fullyQualifiedClass): string
    {
        $reflection = new ReflectionClass($fullyQualifiedClass);
        return $reflection->getShortName();
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function shouldExecute(WorkflowState $state): bool
    {
        if ($this->condition === null) {
            return true;
        }

        return ($this->condition)($state);
    }
}
