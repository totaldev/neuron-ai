<?php

namespace NeuronAI\Observability;

use NeuronAI\Observability\Events\WorkflowEnd;
use NeuronAI\Observability\Events\WorkflowStart;

trait HandleWorkflowEvents
{
    public function workflowStart(\SplObserver $workflow, string $event, WorkflowStart $data)
    {
        if (!$this->inspector->isRecording()) {
            return;
        }

        if ($this->inspector->needTransaction()) {
            $this->inspector->startTransaction($workflow::class)
                ->setType('neuron-workflow')
                ->addContext('execution', $data->executionList);
        } elseif ($this->inspector->canAddSegments()) {
            $this->segments[$workflow::class] = $this->inspector->startSegment('neuron-workflow', $workflow::class)
                ->setColor(self::SEGMENT_COLOR);
        }
    }

    public function workflowEnd(\SplObserver $workflow, string $event, WorkflowEnd $data)
    {
        if (\array_key_exists($workflow::class, $this->segments)) {
            $this->segments[$workflow::class]->addContext('Last Reply', $data->lastReply->jsonSerialize())->end();
        } elseif ($this->inspector->canAddSegments()) {
            $this->inspector->transaction()
                ->addContext('Last Reply', $data->lastReply->jsonSerialize())
                ->setResult('success');
        }
    }
}
