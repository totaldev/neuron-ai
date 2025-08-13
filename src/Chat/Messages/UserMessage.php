<?php

declare(strict_types=1);

namespace NeuronAI\Chat\Messages;

use NeuronAI\Chat\Enums\MessageRole;

/**
 * @method static static make(array|string|int|float|null $content = null)
 */
class UserMessage extends Message
{
    public function __construct(array|string|int|float|null $content)
    {
        parent::__construct(MessageRole::USER, $content);
    }
}
