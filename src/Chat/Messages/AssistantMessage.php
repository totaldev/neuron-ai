<?php

declare(strict_types=1);

namespace NeuronAI\Chat\Messages;

use NeuronAI\Chat\Enums\MessageRole;

/**
 * @method static static make(array|string|int|float|null $content = null, MessageRole $role = MessageRole::ASSISTANT)
 */
class AssistantMessage extends Message
{
    public function __construct(array|string|int|float|null $content, MessageRole $role = MessageRole::ASSISTANT)
    {
        parent::__construct($role, $content);
    }
}
