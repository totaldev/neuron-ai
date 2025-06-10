<?php

namespace NeuronAI\Observability\Events;

use NeuronAI\Chat\Messages\Message;

class WorkflowNodeEnd
{
    public function __construct(
        public string $node,
        public ?Message $lastReply,
    ) {
    }
}
