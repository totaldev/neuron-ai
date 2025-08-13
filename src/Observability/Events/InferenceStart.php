<?php

declare(strict_types=1);

namespace NeuronAI\Observability\Events;

use NeuronAI\Chat\Messages\Message;

class InferenceStart
{
    public function __construct(public Message|false $message)
    {
    }
}
