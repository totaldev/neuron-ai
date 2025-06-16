<?php

namespace NeuronAI\Workflow;

use NeuronAI\Exceptions\WorkflowException;

class WorkflowInterrupt extends WorkflowException
{
    public function __construct(
        protected array $data,
        protected string $currentNode,
        protected WorkflowState $state
    ) {
        parent::__construct('Workflow interrupted for human input');
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCurrentNode(): string
    {
        return $this->currentNode;
    }

    public function getState(): WorkflowState
    {
        return $this->state;
    }
}
