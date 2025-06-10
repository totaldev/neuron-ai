<?php

namespace NeuronAI\Observability\Events;

use NeuronAI\Chat\Messages\Message;

class WorkflowEnd
{
    public function __construct(public ?Message $lastReply)
    {
    }
}
