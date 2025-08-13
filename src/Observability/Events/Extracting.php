<?php

declare(strict_types=1);

namespace NeuronAI\Observability\Events;

use NeuronAI\Chat\Messages\Message;

class Extracting
{
    public function __construct(public Message $message)
    {
    }
}
