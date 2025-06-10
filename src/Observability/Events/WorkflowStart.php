<?php

namespace NeuronAI\Observability\Events;

class WorkflowStart
{
    public function __construct(public array $executionList)
    {
    }
}
