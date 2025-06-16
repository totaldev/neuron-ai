<?php

namespace NeuronAI\Tests\Workflow;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class BeforeInterruptNode extends Node
{
    public function run(WorkflowState $state): WorkflowState
    {
        $state->set('step', 'before_interrupt');
        $state->set('value', 42);
        return $state;
    }
}
