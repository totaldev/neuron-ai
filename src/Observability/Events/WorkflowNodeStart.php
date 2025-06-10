<?php

namespace NeuronAI\Observability\Events;

use NeuronAI\Chat\Messages\Message;

class WorkflowNodeStart
{
    /**
     * @param string $node
     * @param Message[] $messages
     */
    public function __construct(
        public string $node,
        public array $messages,
    ) {
    }
}
